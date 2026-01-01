import requests
import time
from typing import Final, Optional, List
from src.core.logger import logger, transaction_id

ACCESS_TOKEN_VALIDITY_MULTIPLIER: Final[float] = 0.95


# TODO: Extract the Swagger model and use in return values.
class CoreClient:
    def __init__(
        self,
        core_host: str,
        core_port: int,
        core_ssl: bool,
        iam_host: str,
        iam_port: int,
        iam_ssl: bool,
        iam_backend_client_id: str,
        iam_backend_client_secret: str,
    ) -> None:
        self.core_host = core_host
        self.core_port = core_port
        self.core_ssl = core_ssl
        self.iam_host = iam_host
        self.iam_port = iam_port
        self.iam_ssl = iam_ssl
        self.iam_backend_client_id = iam_backend_client_id
        self.iam_backend_client_secret = iam_backend_client_secret

        self.session = requests.Session()
        self.session.headers.update({"Accept": "application/json"})

        self.token = None
        self.token_expires_at = 0

    def get_trip(self, trip_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/trips/{trip_id}"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def get_places(self, trip_id: Optional[str] = None, include: Optional[str] = None) -> List[dict]:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/places"

        params = {}
        if trip_id is not None:
            params["tripId"] = trip_id
            
        if include is not None:
            params["include"] = include

        response = self.session.get(url, params=params, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def get_place(self, place_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/places/{place_id}"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def get_place_album_photos(self, place_id: str, album_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/places/{place_id}/albums/{album_id}/photos"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def create_place_highlight(self, place_id: str, photo_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/places/{place_id}/highlights"
        response = self.session.post(
            url, json={"photo": {"id": photo_id}}, **self._get_request_kwargs()
        )
        response.raise_for_status()

    def create_trip_highlight(self, trip_id: str, photo_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/trips/{trip_id}/highlights"
        response = self.session.post(
            url, json={"photo": {"id": photo_id}}, **self._get_request_kwargs()
        )
        response.raise_for_status()

    def _ensure_authenticated(self) -> None:
        if self.token and time.time() < self.token_expires_at:
            return

        try:
            payload = {
                "clientId": self.iam_backend_client_id,
                "clientSecret": self.iam_backend_client_secret,
            }
            response = requests.post(
                f"{self._get_iam_base_url()}/token",
                json=payload,
                **self._get_request_kwargs(),
            )
            response.raise_for_status()
            data = response.json()

            self.token = data.get("accessToken")
            self.token_expires_at = (
                time.time() + ACCESS_TOKEN_VALIDITY_MULTIPLIER * data.get("expiresIn")
            )

            self.session.headers.update({"Authorization": f"Bearer {self.token}"})

        except Exception as e:
            logger.error(f"Unable to authenticate: {e}")
            raise

    def _get_request_kwargs(self) -> dict:
        kwargs = {"headers": {}}

        tx_id = transaction_id.get()
        if tx_id:
            kwargs["headers"]["Transaction-Id"] = tx_id

        return kwargs

    def _get_core_base_url(self) -> str:
        return f"{'https' if self.core_ssl else 'http'}://{self.core_host}:{self.core_port}"

    def _get_iam_base_url(self) -> str:
        return (
            f"{'https' if self.iam_ssl else 'http'}://{self.iam_host}:{self.iam_port}"
        )
