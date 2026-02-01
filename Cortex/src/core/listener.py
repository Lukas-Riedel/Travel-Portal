import pika
import json
import time
import uuid
import ssl
import threading
import signal
from typing import List, Any, Optional
from src.core.logger import logger
from src.handlers.base_handler import BaseHandler
from src.core.logger import transaction_id
from pika.channel import Channel
from types import FrameType
from src.core.core_client import CoreClient

PROCESSING_STARTED_EVENT_NAME: str = "ProcessingStarted"
PROCESSING_ENDED_EVENT_NAME: str = "ProcessingEnded"
PROCESSING_FAILED_EVENT_NAME: str = "ProcessingFailed"


class EventListener:
    def __init__(
        self,
        core_client: CoreClient,
        handlers: List[BaseHandler],
        rmq_host: str,
        rmq_port: int,
        rmq_vhost: str,
        rmq_user: str,
        rmq_password: str,
        rmq_ssl: bool,
        rmq_heartbeat: int,
        rmq_queue: str,
    ) -> None:
        self.core_client = core_client
        self.handlers: dict[str, BaseHandler] = {}

        for handler in handlers:
            event_names = handler.get_handled_event_names()
            for event_name in event_names:
                self.handlers[event_name] = handler

        self.rmq_host = rmq_host
        self.rmq_port = rmq_port
        self.rmq_vhost = rmq_vhost
        self.rmq_user = rmq_user
        self.rmq_password = rmq_password
        self.rmq_heartbeat = rmq_heartbeat
        self.rmq_queue = rmq_queue

        self.should_stop = False
        self.processing_lock = threading.Lock()

        credentials = pika.PlainCredentials(self.rmq_user, self.rmq_password)
        ssl_options = (
            pika.SSLOptions(context=ssl.create_default_context()) if rmq_ssl else None
        )

        self.params = pika.ConnectionParameters(
            host=self.rmq_host,
            port=self.rmq_port,
            virtual_host=self.rmq_vhost,
            credentials=credentials,
            heartbeat=self.rmq_heartbeat,
            ssl_options=ssl_options,
        )

        self.connection = None
        self.channel = None

    def listen(self) -> None:
        signal.signal(signal.SIGTERM, self._handle_exit_signal)
        signal.signal(signal.SIGINT, self._handle_exit_signal)
        if hasattr(signal, "SIGQUIT"):
            signal.signal(signal.SIGQUIT, self._handle_exit_signal)

        self.connection = pika.SelectConnection(
            self.params,
            on_open_callback=self._on_connection_open,
            on_open_error_callback=self._on_connection_error,
            on_close_callback=self._on_connection_closed,
        )

        try:
            self.connection.ioloop.start()
        except KeyboardInterrupt:
            self.stop()

    def stop(self) -> None:
        if self.connection and not self.connection.is_closed:
            self.connection.close()

        if self.connection and self.connection.ioloop:
            self.connection.ioloop.stop()

    def _handle_exit_signal(self, signum: int, frame: Optional[FrameType]) -> None:
        if self.should_stop:
            return

        logger.info(f"The consumer '{self.consumer_tag}' is being terminated...")
        self.should_stop = True
        if self.channel and self.channel.is_open:
            self.channel.basic_cancel(
                consumer_tag=self.consumer_tag, callback=self._on_consumer_cancelled
            )
        else:
            self._final_cleanup()

    def _final_cleanup(self) -> None:
        with self.processing_lock:
            if self.connection and not self.connection.is_closed:
                self.connection.ioloop.add_callback_threadsafe(self.stop)

    def _on_consumer_cancelled(self, _unused_frame: Any) -> None:
        threading.Thread(target=self._final_cleanup).start()

    def _on_channel_closed(self, channel: Channel, reason: Exception) -> None:
        if self.connection:
            self.connection.close()

    def _on_connection_open(self, _unused_connection: pika.BaseConnection) -> None:
        self.connection.channel(on_open_callback=self._on_channel_open)

    def _on_channel_open(self, channel: Channel) -> None:
        self.channel = channel
        self.channel.add_on_close_callback(self._on_channel_closed)

        self.channel.queue_declare(
            queue=self.rmq_queue, durable=True, callback=self._on_queue_declared
        )

    def _on_queue_declared(self, _unused_frame: Any) -> None:
        self.channel.basic_qos(prefetch_count=1)
        self.consumer_tag = self.channel.basic_consume(self.rmq_queue, self._on_message)

    def _on_connection_error(
        self, _unused_connection: pika.BaseConnection, error: Exception
    ) -> None:
        self.stop()

    def _on_connection_closed(
        self, _unused_connection: pika.BaseConnection, reason: Exception
    ) -> None:
        self.stop()

    def _on_message(
        self,
        ch: Channel,
        method: pika.spec.Basic.Deliver,
        properties: pika.spec.BasicProperties,
        body: bytes,
    ) -> None:
        headers = properties.headers or {}
        tx_id = headers.get("Transaction-Id")
        if not tx_id:
            tx_id = str(uuid.uuid4())

        worker_thread = threading.Thread(
            target=self._process_in_thread,
            args=(method.delivery_tag, tx_id, body),
            daemon=False,
        )
        worker_thread.start()

    def _process_in_thread(self, delivery_tag: int, tx_id: str, body: bytes) -> None:
        with self.processing_lock:
            token = transaction_id.set(tx_id)
            start_time = time.time()
            event = json.loads(body)
            event_name = event.get("name")
            args = event.get("args")

            logger.info(
                f"Received the '{event_name}' event...",
                extra={"event": event},
            )
            self.core_client.create_event(PROCESSING_STARTED_EVENT_NAME, event)

            try:
                if event_name in self.handlers:
                    self.handlers[event_name].handle(args)
                else:
                    logger.error(f"No handler can process the '{event_name}' event.")

                self.core_client.create_event(PROCESSING_ENDED_EVENT_NAME, event)

                self.connection.ioloop.add_callback_threadsafe(
                    lambda: self.channel.basic_ack(delivery_tag=delivery_tag)
                )

            except Exception as e:
                logger.error(
                    f"The processing of the '{event_name} failed. Reason: {str(e)}",
                    extra={"event": event},
                )
                self.core_client.create_event(PROCESSING_FAILED_EVENT_NAME, event)

                self.connection.ioloop.add_callback_threadsafe(
                    lambda: self.channel.basic_nack(
                        delivery_tag=delivery_tag, requeue=False
                    )
                )
            finally:
                duration = round((time.time() - start_time) * 1000)
                logger.info(
                    f"The '{event_name}' event was processed in {duration} milliseconds."
                )
                transaction_id.reset(token)
