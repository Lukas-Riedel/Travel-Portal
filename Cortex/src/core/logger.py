import logging
import logging_loki
import os
import uuid
from dotenv import load_dotenv
from logging import Logger
from typing import Final

load_dotenv()

SERVICE_NAME: Final[str] = "cortex"


class CortexLogger:
    def __init__(
        self,
        version_tag: str,
        grafana_base_url: str,
        grafana_user: str,
        grafana_password: str,
    ) -> None:
        self.version_tag = version_tag
        self.grafana_base_url = grafana_base_url
        self.grafana_user = grafana_user
        self.grafana_password = grafana_password

    def get_logger(self) -> Logger:
        logger = logging.getLogger(SERVICE_NAME)
        logger.setLevel(logging.DEBUG)
        
        transaction_id = str(uuid.uuid4())

        if logger.hasHandlers():
            logger.handlers.clear()

        loki_url = f"{self.grafana_base_url}/loki/api/v1/push"
        if loki_url:
            auth_user = self.grafana_user
            auth_pass = self.grafana_password

            loki_handler = logging_loki.LokiHandler(
                url=loki_url,
                tags={
                    "client_name": SERVICE_NAME,
                    "service": SERVICE_NAME,
                    "version_tag": self.version_tag,
                    "transaction_id": transaction_id,
                },
                auth=(auth_user, auth_pass) if auth_user else None,
                version="1",
            )
            logger.addHandler(loki_handler)

        return logger


logger_provider = CortexLogger(
    os.getenv("VERSION_TAG"),
    os.getenv("GRAFANA_LOKI_ENTRYPOINT"),
    os.getenv("GRAFANA_LOKI_USER"),
    os.getenv("GRAFANA_LOKI_PASSWORD"),
)

logger = logger_provider.get_logger()
