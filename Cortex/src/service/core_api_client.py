from typing import Any

import requests

from src.api.logging_middleware import REQUEST_ORIGIN_HEADER, TRANSACTION_ID_HEADER, USER_ID_HEADER
from src.core.logger import transaction_id, user_id
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

    def request(self, method: str, url: str, **kwargs: Any) -> Any:
        token = self.auth_service.get_service_access_token()
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
            "Accept": "application/json",
            REQUEST_ORIGIN_HEADER: "cortex",
        }

        t_id = transaction_id.get()
        if t_id:
            headers[TRANSACTION_ID_HEADER] = t_id

        u_id = user_id.get()
        if u_id:
            headers[USER_ID_HEADER] = u_id

        response = requests.request(method, url, headers=headers, timeout=15, **kwargs)
        return response.json()

    def get_trips(
        self,
        year: int | None = None,
        trip_type: str = "regular",
        include: str | None = None,
        sort: str | None = None,
    ) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/trips"
        params: dict[str, Any] = {"type": trip_type}
        if year is not None:
            params["year"] = year
        if include:
            params["include"] = include
        if sort:
            params["sort"] = sort

        return self.request("GET", url, params=params)

    def get_trip(self, trip_id: str) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}"

        return self.request("GET", url)

    def get_configuration(self) -> dict[str, Any]:
        url = f"{self.base_url}/configuration"

        return self.request("GET", url)

    def search(self, query: str, include: str | None = None, limit: int | None = None) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/search"
        params: dict[str, Any] = {"query": query}
        if include:
            params["include"] = include
        if limit is not None:
            params["limit"] = limit

        return self.request("GET", url, params=params)

    def create_trip_note(self, trip_id: str, content: str) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/notes"
        payload = {"content": content}

        return self.request("POST", url, json=payload)

    def update_trip_note(self, trip_id: str, note_id: str, content: str | None = None) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/notes/{note_id}"
        payload: dict[str, Any] = {}
        if content is not None:
            payload["content"] = content

        return self.request("PATCH", url, json=payload)

    def create_trip_expense(
        self,
        trip_id: str,
        description: str,
        value: float,
        currency: str,
        expense_type: str,
        subscription_id: str | None = None,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/expenses"
        payload: dict[str, Any] = {
            "description": description,
            "value": value,
            "currency": currency,
            "type": expense_type,
        }
        if subscription_id:
            payload["subscription"] = {"id": subscription_id}

        return self.request("POST", url, json=payload)

    def create_trip_task(
        self,
        trip_id: str,
        title: str,
        priority: str,
        description: str | None = None,
        deadline: int | None = None,
        notification_interval: int | None = None,
        auto_delete: bool = False,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/tasks"
        payload: dict[str, Any] = {
            "title": title,
            "priority": priority,
            "autoDelete": auto_delete,
        }
        if description is not None:
            payload["description"] = description
        if deadline is not None:
            payload["deadline"] = deadline
        if notification_interval is not None:
            payload["notificationInterval"] = notification_interval

        return self.request("POST", url, json=payload)

    def update_trip_task(
        self,
        trip_id: str,
        task_id: str,
        title: str | None = None,
        description: str | None = None,
        priority: str | None = None,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/tasks/{task_id}"
        payload: dict[str, Any] = {}
        if title is not None:
            payload["title"] = title
        if description is not None:
            payload["description"] = description
        if priority is not None:
            payload["priority"] = priority

        return self.request("PATCH", url, json=payload)

    def get_vouchers(self) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/vouchers"

        return self.request("GET", url)

    def get_voucher(self, voucher_id: str) -> dict[str, Any]:
        url = f"{self.base_url}/vouchers/{voucher_id}"

        return self.request("GET", url)

    def create_voucher(
        self,
        code: str,
        issuer: str,
        value: float,
        currency: str,
        expiration: int | None = None,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/vouchers"
        payload: dict[str, Any] = {
            "code": code,
            "issuer": issuer,
            "value": value,
            "currency": currency,
        }
        if expiration is not None:
            payload["expiration"] = expiration

        return self.request("POST", url, json=payload)

    def update_voucher(self, voucher_id: str, value: float | None = None) -> dict[str, Any]:
        url = f"{self.base_url}/vouchers/{voucher_id}"
        payload: dict[str, Any] = {}
        if value is not None:
            payload["value"] = value

        return self.request("PATCH", url, json=payload)

    def get_subscriptions(self) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/subscriptions"

        return self.request("GET", url)

    def create_subscription(
        self,
        description: str,
        value: float,
        currency: str,
        expiration: int,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/subscriptions"
        payload: dict[str, Any] = {
            "description": description,
            "value": value,
            "currency": currency,
            "expiration": expiration,
        }

        return self.request("POST", url, json=payload)
