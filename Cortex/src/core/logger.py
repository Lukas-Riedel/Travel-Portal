import contextvars
import datetime
import json
import logging
import os
import sys

from dotenv import load_dotenv

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
            app_name: str,
    ) -> None:
        self.app_name = app_name

    def get_logger(self) -> logging.Logger:
        logger = logging.getLogger(self.app_name)
        logger.setLevel(logging.DEBUG)

        if logger.hasHandlers():
            logger.handlers.clear()

        handler = logging.StreamHandler(sys.stdout)
        handler.addFilter(TransactionFilter())
        handler.setFormatter(JsonFormatter())
        logger.addHandler(handler)

        return logger


logger_provider = Logger(
    os.getenv("APP_NAME"),
)

logger = logger_provider.get_logger()
