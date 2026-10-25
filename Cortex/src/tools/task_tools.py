from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseTaskTools(BaseCoreTools, ABC):
    def _extract_task(
        self,
        task: dict[str, Any],
        trip: dict[str, Any],
    ) -> dict[str, Any]:
        return {
            "id": task.get("id"),
            "trip_id": trip.get("id"),
            "trip_name": f"{trip.get('name')} {trip.get('year')}",
            "title": task.get("title"),
            "description": task.get("description"),
            "priority": task.get("priority"),
            "deadline": self._epoch_to_iso(
                epoch=task.get("deadline"), user_tz=self._get_user_timezone()
            ),
        }


class TripTaskReadTools(BaseTaskTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and view actionable tasks and to-do items recorded for trips in Travel Portal. Activate and apply this skill when the user asks about their tasks, to-do items, or reminders (e.g. "what tasks do I have?", "show me high-priority tasks", "what do I need to do before my Japan trip?").'

    def _get_supported_skill(self) -> str:
        return "trip.task.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_tasks,
        ]

    def get_tasks(
        self,
        trip_id: str | None,
        max_deadline: int | None,
    ) -> list[dict[str, Any]]:
        """Retrieves tasks from Travel Portal with optional filters.
        Use this when the user asks about their to-do items, tasks, or reminders (e.g. "what tasks do I have?", "what do I need to do before my next trip?").

        Args:
            trip_id: Filter tasks to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve tasks across all regular trips.
            max_deadline: Filter to tasks whose deadline is at or before this Unix epoch timestamp.
                Use convert_datetime_to_epoch to convert a date to epoch. Pass None to return tasks regardless of deadline.

        Returns:
            A list of task objects, where each object contains:
            - id (string | null): Unique identifier of the task, or null if the task is auto-generated. Use it when updating or deleting the task.
            - trip_id (string): Unique identifier of the trip this task belongs to.
            - trip_name (string): Name and year of the associated trip (e.g. `"Japan 2025"`).
            - title (string): Title of the task.
            - description (string | null): Optional description with additional details.
            - priority (string): Priority level ('lowest', 'low', 'medium', 'high', 'highest').
            - deadline (string | null): Deadline in ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`), or null if no deadline.
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id)
            self._verify_error_response(trip)
            trips = [trip]
        else:
            trips = self.core_client.get_trips(trip_type="regular", include="tasks")
            self._verify_error_response(trips)

        result = []
        for trip in trips:
            if not self._is_trip_visible(trip):
                continue

            for task in trip.get("tasks", []):
                if not task.get("actionable"):
                    continue
                if (
                    max_deadline is not None
                    and task.get("deadline") is not None
                    and task["deadline"] > max_deadline
                ):
                    continue
                result.append(self._extract_task(task=task, trip=trip))

        return result


class TripTaskEditTools(BaseTaskTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create, update, and delete tasks and to-do items for trips in Travel Portal. Activate and apply this skill when the user asks to add, create, modify, edit, or delete a task for a trip (e.g. "add a task to my Japan trip", "mark task as done", "delete the packing task", "change priority of the visa task to high").'

    def _get_supported_skill(self) -> str:
        return "trip.task.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_trip_task,
            self.update_trip_task_title,
            self.update_trip_task_description,
            self.update_trip_task_priority,
            self.delete_trip_task,
        ]

    def create_trip_task(
        self,
        trip_id: str,
        title: str,
        priority: str,
        description: str | None,
        deadline: int | None,
        notification_interval: int | None,
        auto_delete: bool,  # noqa: FBT001
    ) -> dict[str, Any]:
        """Creates a new task for a specific trip in Travel Portal.
        Use this when the user asks to add, create, or record a task, to-do item, or reminder for a trip.
        Always resolve the trip ID first using trip tools. Never ask the user for a UUID.

        Business rules (enforce strictly - reject with a clear error message if violated):
        - `deadline` is required when `notification_interval` is set (a repeating notification needs an anchor point).
        - `notification_interval` and `auto_delete` are mutually exclusive - a task cannot both notify repeatedly and auto-delete.
        - `deadline`, if provided, must be in the future. Use convert_datetime_to_epoch to convert a user-supplied date.

        Args:
            trip_id: The unique identifier of the trip the task belongs to. Resolve via trip tools.
            title: Short title of the task (e.g. "Buy JR Pass", "Book airport transfer").
            priority: Priority level of the task. Must be one of: 'lowest', 'low', 'medium', 'high', 'highest'.
            description: Optional longer description or notes for the task. Pass None if not provided.
            deadline: Optional deadline as a Unix epoch timestamp (seconds). Use convert_datetime_to_epoch to convert a date. Must be in the future.
            notification_interval: Optional interval in seconds between repeated deadline notifications (e.g. 86400 for daily). Requires `deadline` to be set. Cannot be combined with `auto_delete=True`.
            auto_delete: Whether to automatically delete the task once its deadline is reached. Cannot be combined with `notification_interval`.

        Returns:
            The created task object containing:
            - id (string): Unique identifier of the task.
            - trip_id (string): Unique identifier of the trip.
            - trip_name (string): Name and year of the associated trip.
            - title (string): Title of the task.
            - description (string | null): Optional description.
            - priority (string): Priority level.
            - deadline (string | null): Deadline in ISO 8601 format with timezone offset, or null.
        """
        trip = self.core_client.get_trip(trip_id)
        self._verify_error_response(trip)

        task = self.core_client.create_trip_task(
            trip_id=trip_id,
            title=title,
            priority=priority,
            description=description,
            deadline=deadline,
            notification_interval=notification_interval,
            auto_delete=auto_delete,
        )
        self._verify_error_response(task)

        return self._extract_task(task=task, trip=trip)

    def update_trip_task_title(
        self,
        trip_id: str,
        task_id: str,
        title: str,
    ) -> dict[str, Any]:
        """Updates the title of an existing task in Travel Portal.
        Use this when the user asks to rename a task or change its title.
        Always resolve the trip ID first using trip tools and find the task ID via get_tasks. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the task belongs to. Resolve via trip tools.
            task_id: The unique identifier of the task to update. Resolve via get_tasks.
            title: New title for the task.

        Returns:
            The updated task object containing:
            - id (string): Unique identifier of the task.
            - trip_id (string): Unique identifier of the trip.
            - trip_name (string): Name and year of the associated trip.
            - title (string): Title of the task.
            - description (string | null): Optional description.
            - priority (string): Priority level.
            - deadline (string | null): Deadline in ISO 8601 format with timezone offset, or null.
        """
        trip = self.core_client.get_trip(trip_id)
        self._verify_error_response(trip)

        task = self.core_client.update_trip_task(
            trip_id=trip_id,
            task_id=task_id,
            title=title,
        )
        self._verify_error_response(task)

        return self._extract_task(task=task, trip=trip)

    def update_trip_task_description(
        self,
        trip_id: str,
        task_id: str,
        description: str,
    ) -> dict[str, Any]:
        """Updates the description of an existing task in Travel Portal.
        Use this when the user asks to change, rewrite, or add a description of an existing task.
        Always resolve the trip ID first using trip tools and find the task ID via get_tasks. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the task belongs to. Resolve via trip tools.
            task_id: The unique identifier of the task to update. Resolve via get_tasks.
            description: New description for the task.

        Returns:
            The updated task object containing:
            - id (string): Unique identifier of the task.
            - trip_id (string): Unique identifier of the trip.
            - trip_name (string): Name and year of the associated trip.
            - title (string): Title of the task.
            - description (string | null): Optional description.
            - priority (string): Priority level.
            - deadline (string | null): Deadline in ISO 8601 format with timezone offset, or null.
        """
        trip = self.core_client.get_trip(trip_id)
        self._verify_error_response(trip)

        task = self.core_client.update_trip_task(
            trip_id=trip_id,
            task_id=task_id,
            description=description,
        )
        self._verify_error_response(task)

        return self._extract_task(task=task, trip=trip)

    def update_trip_task_priority(
        self,
        trip_id: str,
        task_id: str,
        priority: str,
    ) -> dict[str, Any]:
        """Updates the priority of an existing task in Travel Portal.
        Use this when the user asks to change the priority of an existing task.
        Always resolve the trip ID first using trip tools and find the task ID via get_tasks. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the task belongs to. Resolve via trip tools.
            task_id: The unique identifier of the task to update. Resolve via get_tasks.
            priority: New priority level. Must be one of: 'lowest', 'low', 'medium', 'high', 'highest'.

        Returns:
            The updated task object containing:
            - id (string): Unique identifier of the task.
            - trip_id (string): Unique identifier of the trip.
            - trip_name (string): Name and year of the associated trip.
            - title (string): Title of the task.
            - description (string | null): Optional description.
            - priority (string): Priority level.
            - deadline (string | null): Deadline in ISO 8601 format with timezone offset, or null.
        """
        trip = self.core_client.get_trip(trip_id)
        self._verify_error_response(trip)

        task = self.core_client.update_trip_task(
            trip_id=trip_id,
            task_id=task_id,
            priority=priority,
        )
        self._verify_error_response(task)

        return self._extract_task(task=task, trip=trip)

    def delete_trip_task(self, trip_id: str, task_id: str) -> dict[str, Any]:
        """Deletes an existing task from a trip in Travel Portal.
        Use this when the user asks to delete, remove, or dismiss a task.
        Always resolve the trip ID first using trip tools and find the task ID via get_tasks. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the task belongs to. Resolve via trip tools.
            task_id: The unique identifier of the task to delete. Resolve via get_tasks.

        Returns:
            A confirmation object containing:
            - deleted (boolean): True if deletion succeeded.
            - id (string): Unique identifier of the deleted task.
        """
        result = self.core_client.delete_trip_task(
            trip_id=trip_id,
            task_id=task_id,
        )
        self._verify_error_response(result)

        return {"deleted": True, "id": task_id}
