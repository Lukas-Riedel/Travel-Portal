import logging
import logging_loki
import os
import json
import contextvars
import datetime
from dotenv import load_dotenv
from logging import Logger
from typing import Final

load_dotenv()

transaction_id = contextvars.ContextVar("transaction_id", default="")
request_origin = contextvars.ContextVar("request_origin", default="")


class TransactionFilter(logging.Filter):
    def filter(self, record: logging.LogRecord) -> bool:
        record.transaction_id = transaction_id.get()
        record.request_origin = request_origin.get()
        return True


class JsonFormatter(logging.Formatter):
    def format(self, record: logging.LogRecord) -> str:
        tx_id = getattr(record, "transaction_id", "")
        origin = getattr(record, "request_origin", "")

        dt = datetime.datetime.fromtimestamp(record.created).astimezone()

        log_record = {
            "message": record.getMessage(),
            "level": record.levelno * 10,
            "level_name": record.levelname,
            "channel": record.name,
            "datetime": dt.isoformat(),
            "transaction_id": tx_id,
            "request_origin": origin,
        }

        event = getattr(record, "event", None)
        if event:
            log_record["ctxt_name"] = event.get("name")
            log_record["ctxt_args"] = json.dumps(event.get("args"), default=str)

        return json.dumps(log_record, default=str)


class Logger:
    def __init__(
        self,
        app_name : str,
        version_tag: str,
        grafana_base_url: str,
        grafana_client_name: str,
        grafana_user: str,
        grafana_password: str,
    ) -> None:
        self.app_name = app_name
        self.version_tag = version_tag
        self.grafana_base_url = grafana_base_url
        self.grafana_client_name = grafana_client_name
        self.grafana_user = grafana_user
        self.grafana_password = grafana_password

    def get_logger(self) -> Logger:
        logger = logging.getLogger(self.app_name)
        logger.setLevel(logging.DEBUG)

        if logger.hasHandlers():
            logger.handlers.clear()

        loki_url = f"{self.grafana_base_url}/loki/api/v1/push"
        if loki_url:
            auth_user = self.grafana_user
            auth_pass = self.grafana_password

            loki_handler = logging_loki.LokiHandler(
                url=loki_url,
                tags={
                    "host": self.grafana_client_name,
                    "service": self.app_name,
                    "version_tag": self.version_tag,
                },
                auth=(auth_user, auth_pass) if auth_user else None,
                version="1",
            )
            loki_handler.addFilter(TransactionFilter())
            loki_handler.setFormatter(JsonFormatter())
            logger.addHandler(loki_handler)

        return logger


logger_provider = Logger(
    os.getenv("APP_NAME"),
    os.getenv("VERSION_TAG"),
    os.getenv("GRAFANA_LOKI_ENTRYPOINT"),
    os.getenv("GRAFANA_LOKI_CLIENT_NAME"),
    os.getenv("GRAFANA_LOKI_USER"),
    os.getenv("GRAFANA_LOKI_PASSWORD"),
)

logger = logger_provider.get_logger()
