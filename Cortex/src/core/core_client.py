import requests
import time
from typing import Final, Optional, List
from src.core.logger import logger, transaction_id

ACCESS_TOKEN_VALIDITY_MULTIPLIER: Final[float] = 0.95


# TODO: Extract the Swagger model and use in return values.
class CoreClient:
    def __init__(
        self,
        app_name: str,
        core_host: str,
        core_port: int,
        core_ssl: bool,
        iam_host: str,
        iam_port: int,
        iam_ssl: bool,
        iam_backend_client_id: str,
        iam_backend_client_secret: str,
    ) -> None:
        self.app_name = app_name
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

    def create_generative_content(self, prompt_template: str, context: dict) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/generativecontent"
        response = self.session.post(
            url, json={"promptTemplate": prompt_template, "context": context}, **self._get_request_kwargs()
        )
        response.raise_for_status()
        return response.json()

    def get_highlight(self, highlight_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/highlights/{highlight_id}"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def update_highlight_quality_attributes(
        self, highlight_id: str, attributes: dict
    ) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/highlights/{highlight_id}"
        response = self.session.patch(
            url, json={"attributes": attributes}, **self._get_request_kwargs()
        )
        response.raise_for_status()

    def get_trip(self, trip_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/trips/{trip_id}"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def get_category(self, category_id: str) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/categories/{category_id}"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def get_year(self, year: int) -> dict:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/years/{year}"
        response = self.session.get(url, **self._get_request_kwargs())
        response.raise_for_status()
        return response.json()

    def get_places(
        self,
        category_id: Optional[str] = None,
        year: Optional[int] = None,
        trip_id: Optional[str] = None,
        photo_id: Optional[str] = None,
        include: Optional[str] = None,
    ) -> List[dict]:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/places"

        params = {}
        if category_id is not None:
            params["categoryId"] = category_id

        if year is not None:
            params["year"] = year

        if trip_id is not None:
            params["tripId"] = trip_id

        if photo_id is not None:
            params["photoId"] = photo_id

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

    def remove_place_highlight(self, place_id: str, highlight_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/places/{place_id}/highlights/{highlight_id}"
        response = self.session.delete(url, **self._get_request_kwargs())
        response.raise_for_status()

    def create_trip_highlight(self, trip_id: str, photo_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/trips/{trip_id}/highlights"
        response = self.session.post(
            url, json={"photo": {"id": photo_id}}, **self._get_request_kwargs()
        )
        response.raise_for_status()

    def remove_trip_highlight(self, trip_id: str, highlight_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/trips/{trip_id}/highlights/{highlight_id}"
        response = self.session.delete(url, **self._get_request_kwargs())
        response.raise_for_status()

    def create_category_highlight(self, category_id: str, photo_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/categories/{category_id}/highlights"
        response = self.session.post(
            url, json={"photo": {"id": photo_id}}, **self._get_request_kwargs()
        )
        response.raise_for_status()

    def remove_category_highlight(self, category_id: str, highlight_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/categories/{category_id}/highlights/{highlight_id}"
        response = self.session.delete(url, **self._get_request_kwargs())
        response.raise_for_status()

    def create_year_highlight(self, year: int, photo_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/years/{year}/highlights"
        response = self.session.post(
            url, json={"photo": {"id": photo_id}}, **self._get_request_kwargs()
        )
        response.raise_for_status()

    def remove_year_highlight(self, year: int, highlight_id: str) -> None:
        self._ensure_authenticated()

        url = f"{self._get_core_base_url()}/years/{year}/highlights/{highlight_id}"
        response = self.session.delete(url, **self._get_request_kwargs())
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

        kwargs["headers"]["Request-Origin"] = self.app_name

        return kwargs

    def _get_core_base_url(self) -> str:
        return f"{'https' if self.core_ssl else 'http'}://{self.core_host}:{self.core_port}"

    def _get_iam_base_url(self) -> str:
        return (
            f"{'https' if self.iam_ssl else 'http'}://{self.iam_host}:{self.iam_port}"
        )
