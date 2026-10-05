from typing import Any

import requests

from src.core.logger import transaction_id
from src.service.authentication_service import AuthenticationService


# TODO: Deadlock risk — Core calls Cortex synchronously (blocking) and Cortex's agentic AI
# callback calls Core back to fetch data. If Core's thread pool is exhausted waiting for Cortex,
# it cannot serve Cortex's callback request → deadlock.
#
# Proposed fix: decouple the call chain via a message queue (RabbitMQ / Redis).
# Core enqueues the agentic AI request instead of calling Cortex directly over HTTP,
# and exposes a status/result polling endpoint. Cortex consumes tasks from the queue,
# processes them (including calling Core back for data), and writes the result back.
# Core then returns the result via the polling endpoint once Cortex signals completion.
# This breaks the synchronous call cycle entirely.
#
# Until that is implemented: every Core API call in this class MUST have an explicit timeout
# set to prevent threads from blocking indefinitely and masking the deadlock.
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
            "Request-Origin": "cortex",
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

    def get_trip(self, trip_id: str, include: str | None = None) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}"
        params: dict[str, Any] = {}
        if include:
            params["include"] = include

        response = requests.get(url, params=params, headers=self.get_headers(), timeout=15)

        return response.json()

    def search(self, query: str, include: str | None = None, limit: int | None = None) -> list[dict[str, Any]]:
        url = f"{self.base_url}/search"
        params: dict[str, Any] = {"query": query}
        if include:
            params["include"] = include
        if limit is not None:
            params["limit"] = limit

        response = requests.get(url, params=params, headers=self.get_headers(), timeout=15)

        return response.json()

    def create_trip_note(self, trip_id: str, content: str) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/notes"
        payload = {"content": content}

        response = requests.post(url, json=payload, headers=self.get_headers(), timeout=15)

        return response.json()
