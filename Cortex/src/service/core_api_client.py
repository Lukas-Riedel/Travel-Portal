from typing import Any
import requests
from src.core.logger import logger, transaction_id
from src.service.authentication_service import AuthenticationService


class CoreApiClient:
    def __init__(self, core_host: str, core_port: int, authentication_service: AuthenticationService):
        self.base_url = f"http://{core_host}:{core_port}"
        self.auth_service = authentication_service

    def get_headers(self) -> dict[str, str]:
        token = self.auth_service.get_service_access_token()
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        }

        t_id = transaction_id.get()
        if t_id:
            headers["Transaction-Id"] = t_id

        return headers

    def get_trips(
        self,
        year: int | None = None,
        trip_type: str = "regular",
        include: str | None = None,
        sort: str | None = None,
    ) -> list[dict[str, Any]]:
        url = f"{self.base_url}/trips"
        params: dict[str, Any] = {"type": trip_type}
        if year is not None:
            params["year"] = year
        if include:
            params["include"] = include
        if sort:
            params["sort"] = sort

        response = requests.get(url, params=params, headers=self.get_headers(), timeout=15)

        return response.json()

    def create_trip_note(self, trip_id: str, content: str) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/notes"
        payload = {"content": content}

        response = requests.post(url, json=payload, headers=self.get_headers(), timeout=15)

        return response.json()
