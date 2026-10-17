from typing import Any

import requests

from src.api.logging_middleware import (
    REQUEST_ORIGIN_HEADER,
    TRANSACTION_ID_HEADER,
    USER_ID_HEADER,
    USERNAME_HEADER,
)
from src.client.iam_client import IamClient
from src.core.logger import transaction_id, user_id, username


# TODO: Deadlock risk - Core calls Cortex synchronously (blocking) and Cortex's agentic AI
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
class CoreClient:
    def __init__(self, core_host: str, core_port: int, iam_client: IamClient) -> None:
        self.base_url: str = f"http://{core_host}:{core_port}"
        self.auth_service: IamClient = iam_client

    def _request(self, method: str, url: str, **kwargs: Any) -> Any:
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

        u_name = username.get()
        if u_name:
            headers[USERNAME_HEADER] = u_name

        response = requests.request(
            method=method, url=url, headers=headers, timeout=15, **kwargs
        )
        return response.json()

    def get_address(
        self,
        latitude: float,
        longitude: float,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/address"
        params = {"latitude": latitude, "longitude": longitude}

        return self._request(method="GET", url=url, params=params)

    def get_trips(
        self,
        year: int | None = None,
        trip_type: str = "regular",
        include: str | None = None,
        sort: str | None = None,
    ) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/trips"
        params = {"type": trip_type}
        if year is not None:
            params["year"] = year
        if include:
            params["include"] = include
        if sort:
            params["sort"] = sort

        return self._request(method="GET", url=url, params=params)

    def get_trip(self, trip_id: str) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}"

        return self._request(method="GET", url=url)

    def get_configuration(self) -> dict[str, Any]:
        url = f"{self.base_url}/configuration"

        return self._request(method="GET", url=url)

    def search(
        self, query: str, include: str | None = None, limit: int | None = None
    ) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/search"
        params = {"query": query}
        if include:
            params["include"] = include
        if limit is not None:
            params["limit"] = limit

        return self._request(method="GET", url=url, params=params)

    def create_trip_note(self, trip_id: str, content: str) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/notes"
        payload = {"content": content}

        return self._request(method="POST", url=url, json=payload)

    def update_trip_note(
        self, trip_id: str, note_id: str, content: str | None = None
    ) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/notes/{note_id}"
        payload = {}
        if content is not None:
            payload["content"] = content

        return self._request(method="PATCH", url=url, json=payload)

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
        payload = {
            "description": description,
            "value": value,
            "currency": currency,
            "type": expense_type,
        }
        if subscription_id:
            payload["subscription"] = {"id": subscription_id}

        return self._request(method="POST", url=url, json=payload)

    def create_trip_task(
        self,
        trip_id: str,
        title: str,
        priority: str,
        description: str | None = None,
        deadline: int | None = None,
        notification_interval: int | None = None,
        *,
        auto_delete: bool = False,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/tasks"
        payload = {
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

        return self._request(method="POST", url=url, json=payload)

    def update_trip_task(
        self,
        trip_id: str,
        task_id: str,
        title: str | None = None,
        description: str | None = None,
        priority: str | None = None,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/trips/{trip_id}/tasks/{task_id}"
        payload = {}
        if title is not None:
            payload["title"] = title
        if description is not None:
            payload["description"] = description
        if priority is not None:
            payload["priority"] = priority

        return self._request(method="PATCH", url=url, json=payload)

    def get_vouchers(self) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/vouchers"

        return self._request(method="GET", url=url)

    def get_voucher(self, voucher_id: str) -> dict[str, Any]:
        url = f"{self.base_url}/vouchers/{voucher_id}"

        return self._request(method="GET", url=url)

    def create_voucher(
        self,
        code: str,
        issuer: str,
        value: float,
        currency: str,
        expiration: int | None = None,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/vouchers"
        payload = {
            "code": code,
            "issuer": issuer,
            "value": value,
            "currency": currency,
        }
        if expiration is not None:
            payload["expiration"] = expiration

        return self._request(method="POST", url=url, json=payload)

    def update_voucher(
        self, voucher_id: str, value: float | None = None
    ) -> dict[str, Any]:
        url = f"{self.base_url}/vouchers/{voucher_id}"
        payload = {}
        if value is not None:
            payload["value"] = value

        return self._request(method="PATCH", url=url, json=payload)

    def get_subscriptions(self) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/subscriptions"

        return self._request(method="GET", url=url)

    def create_subscription(
        self,
        description: str,
        value: float,
        currency: str,
        expiration: int,
    ) -> dict[str, Any]:
        url = f"{self.base_url}/subscriptions"
        payload = {
            "description": description,
            "value": value,
            "currency": currency,
            "expiration": expiration,
        }

        return self._request(method="POST", url=url, json=payload)

    def get_places(
        self,
        place_type: str = "regular",
        trip_id: str | None = None,
        label_id: str | None = None,
        category_id: str | None = None,
        year: int | None = None,
        min_start: int | None = None,
        max_end: int | None = None,
        include: str | None = None,
        sort: str | None = None,
    ) -> list[dict[str, Any]] | dict[str, Any]:
        url = f"{self.base_url}/places"
        params = {"type": place_type}
        if trip_id is not None:
            params["tripId"] = trip_id
        if label_id is not None:
            params["labelId"] = label_id
        if category_id is not None:
            params["categoryId"] = category_id
        if year is not None:
            params["year"] = year
        if min_start is not None:
            params["minStart"] = min_start
        if max_end is not None:
            params["maxEnd"] = max_end
        if include is not None:
            params["include"] = include
        if sort is not None:
            params["sort"] = sort

        return self._request(method="GET", url=url, params=params)

    def get_place(self, place_id: str) -> dict[str, Any]:
        url = f"{self.base_url}/places/{place_id}"

        return self._request(method="GET", url=url)

    def create_place(self, name: str, address: str, place_type: str) -> dict[str, Any]:
        url = f"{self.base_url}/places"
        params = {"address": address, "type": place_type}
        payload = {"name": name}

        return self._request(method="POST", url=url, params=params, json=payload)

    def update_place_excerpt(
        self, place_id: str, excerpt: str | None
    ) -> dict[str, Any]:
        url = f"{self.base_url}/places/{place_id}"
        payload = {"excerpt": excerpt}

        return self._request(method="PATCH", url=url, json=payload)
