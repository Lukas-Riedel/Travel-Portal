import requests
import time
from typing import Final, Optional, List
from src.core.logger import logger

ACCESS_TOKEN_VALIDITY_MULTIPLIER: Final[float] = 0.95


# TODO: Extract the Swagger model and use in return values.
class CoreClient:
    def __init__(
        self,
        core_base_url: str,
        iam_base_url: str,
        iam_backend_client_id: str,
        iam_backend_client_secret: str,
    ) -> None:
        self.core_base_url = core_base_url
        self.iam_base_url = iam_base_url
        self.iam_backend_client_id = iam_backend_client_id
        self.iam_backend_client_secret = iam_backend_client_secret

        self.session = requests.Session()
        self.session.headers.update({"Accept": "application/json"})

        self.token = None
        self.token_expires_at = 0

    def get_places(self) -> dict:
        self._ensure_authenticated()

        url = f"{self.core_base_url}/places"

        response = self.session.get(url)
        response.raise_for_status()
        return response.json()

    def get_place(self, place_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self.core_base_url}/places/{place_id}"
        response = self.session.get(url)
        response.raise_for_status()
        return response.json()

    def get_place_album_photos(self, place_id: str, album_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self.core_base_url}/places/{place_id}/albums/{album_id}/photos"
        response = self.session.get(url)
        response.raise_for_status()
        return response.json()

    def create_place_highlight(self, place_id: str, photo_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self.core_base_url}/places/{place_id}/highlights"
        response = self.session.post(url, json={"photo": {"id": photo_id}})
        response.raise_for_status()

    def _ensure_authenticated(self) -> None:
        if self.token and time.time() < self.token_expires_at:
            return

        try:
            payload = {
                "clientId": self.iam_backend_client_id,
                "clientSecret": self.iam_backend_client_secret,
            }
            response = requests.post(f"{self.iam_base_url}/token", json=payload)
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
