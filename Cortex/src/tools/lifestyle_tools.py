from abc import ABC
from datetime import date, datetime, time, timedelta
from typing import Any, Final

from src.tools.base_tools import BaseCoreTools

LIFESTYLE_SAMPLE_LIMIT: Final[int] = 50


class BaseLifestyleTools(BaseCoreTools, ABC):
    def _extract_event(self, event: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": event.get("id"),
            "name": event.get("content"),
            "calories": abs(float(event.get("calories"))),
        }


class LifestyleReadTools(BaseLifestyleTools):
    def _get_description(self) -> str:
        return 'Provides capabilities to query the user\'s latest recorded body weight and recent daily calorie intake, expenditure, and deficit in Travel Portal. Activate and apply this skill when the user asks about their current or latest weight, what they ate over a recent period, their calorie expenditure, or their daily calorie deficit (e.g. "what is my current weight?", "what did I eat yesterday?", "what was my energy deficit last Friday?").'

    def _get_supported_skill(self) -> str:
        return "lifestyle.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_current_weight,
            self.get_lifestyle_calorie_days,
        ]

    def get_current_weight(self) -> dict[str, Any] | None:
        """Retrieves the user's latest recorded body weight from Travel Portal.
        Use this when the user asks for their current, latest, or most recent recorded weight (e.g. "what is my current weight?").

        Returns:
            The weight object containing:
            - weight (number): Recorded body weight in kilograms.
            - date (string): Date of the measurement in the user's local timezone, formatted as DD.MM.YYYY.
            - time (string): Local measurement time in 24-hour HH:MM format.
        """
        events = self.core_client.get_lifestyle_events(event_type="weight")
        self._verify_error_response(events)

        latest = max(events, key=lambda event: int(event.get("timestamp", 0)))
        user_tz = self._get_user_timezone()

        return {
            "weight": float(latest.get("content")),
            "date": self._epoch_to_date(epoch=latest.get("timestamp"), user_tz=user_tz),
            "time": self._epoch_to_time(epoch=latest.get("timestamp"), user_tz=user_tz),
        }

    def get_lifestyle_calorie_days(self, days: int) -> list[dict[str, Any]]:
        """Retrieves daily calorie intake items, expenditure items, and deficit.
        Use this when the user asks what they ate, how much energy they expended, or what their calorie deficit was
        over a recent period (e.g. "what did I eat yesterday?", "show my calorie deficit for the last 7 days").
        The requested period includes today and the preceding days, measured by local calendar date in the user's timezone.

        Args:
            days: Number of local calendar dates to return, including today (e.g. 7 for "the last 7 days"). Ask the user for the period if it is not clear from their request.

        Returns:
            A list of one object per requested local date, in chronological order from oldest to newest. Each object contains:
            - date (string): Local date formatted as DD.MM.YYYY.
            - weight (number): Latest body weight recorded on that local date, in kilograms,.
            - steps (integer): Total step count for that local date.
            - deficit (number): Net calories for that date, computed as deficit_breakdown.basal_metabolism + deficit_breakdown.expenditure - deficit_breakdown.intake. A positive value is a deficit, a negative value is a calorie surplus.
            - deficit_breakdown (object): Daily calorie totals by source. The basal_metabolism, intake, and expenditure values are non-negative calorie totals, intake is subtracted when computing deficit.
            - intake (list): Food and drink events with a calorie value. Each item contains id (string), name (string), and calories (number).
            - expenditure (list): Manually recorded activity events with a calorie value. Each item contains id (string), name (string), and calories (number). Steps and basal metabolism contribute to deficit but are not listed here.
        """
        user_tz = self._get_user_timezone()
        now = datetime.now(user_tz)
        first_date = now.date() - timedelta(days=days - 1)
        start = self._get_local_midnight(day=first_date, user_tz=user_tz)

        events = self.core_client.get_lifestyle_events(
            from_timestamp=int(start.timestamp()), to_timestamp=int(now.timestamp())
        )
        self._verify_error_response(events)

        daily = {}
        weight_timestamps = {}
        for offset in range(days):
            day = first_date + timedelta(days=offset)
            day_start = self._get_local_midnight(day=day, user_tz=user_tz)
            local_date = self._epoch_to_date(
                epoch=int(day_start.timestamp()), user_tz=user_tz
            )

            daily[local_date] = {
                "date": local_date,
                "weight": None,
                "steps": 0,
                "deficit": 0,
                "deficit_breakdown": {
                    "basal_metabolism": 0,
                    "intake": 0,
                    "expenditure": 0,
                },
                "intake": [],
                "expenditure": [],
            }
            weight_timestamps[local_date] = -1

        for event in events:
            timestamp = int(event.get("timestamp", 0))
            date = self._epoch_to_date(epoch=timestamp, user_tz=user_tz)
            if date not in daily:
                continue

            event_type = event.get("type")
            if event_type == "weight":
                if timestamp >= weight_timestamps[date]:
                    daily[date]["weight"] = float(event.get("content"))
                    weight_timestamps[date] = timestamp

            if event_type == "steps":
                daily[date]["steps"] += int(float(event.get("content")))

            if event.get("calories") is None:
                continue

            calories = abs(float(event.get("calories")))

            if event_type == "intake":
                daily[date]["intake"].insert(0, self._extract_event(event))
                daily[date]["deficit_breakdown"]["intake"] += calories
            elif event_type == "expense":
                daily[date]["expenditure"].insert(0, self._extract_event(event))
                daily[date]["deficit_breakdown"]["expenditure"] += calories
            elif event_type == "steps":
                daily[date]["deficit_breakdown"]["expenditure"] += calories
            elif event_type == "basalMetabolism":
                daily[date]["deficit_breakdown"]["basal_metabolism"] += calories

        for day in daily.values():
            breakdown = day["deficit_breakdown"]
            day["deficit"] = (
                breakdown["basal_metabolism"]
                + breakdown["expenditure"]
                - breakdown["intake"]
            )

        return list(daily.values())

    def _get_local_midnight(self, day: date, user_tz: Any) -> datetime:
        midnight = datetime.combine(date=day, time=time.min)
        return user_tz.localize(midnight)


class LifestyleEditTools(BaseLifestyleTools):
    def _get_description(self) -> str:
        return 'Provides capabilities to record food and drink intake, record manually reported physical activity expenditure, and delete lifestyle events in Travel Portal. Activate and apply this skill when the user asks to log or record something they ate or drank, log or record an activity they performed, or delete, remove, or discard a recorded intake or activity (e.g. "I had a chicken sandwich for lunch", "log my usual breakfast", "I went for a run", "record my bike ride yesterday", "delete the lunch I just logged"). For intake, use recent food history to check for a corresponding previously recorded item and its calories before recording; activity can be recorded directly.'

    def _get_supported_skill(self) -> str:
        return "lifestyle.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_recent_lifestyle_intake_events,
            self.record_lifestyle_intake,
            self.record_lifestyle_activity,
            self.delete_lifestyle_event,
        ]

    def get_recent_lifestyle_intake_events(self) -> list[dict[str, Any]]:
        """Retrieves recent food and drink intake events from history.
        Use this to review the user's recent food and drink entries (e.g. "oatmeal", "Greek yogurt") and their recorded calories.

        Returns:
            A list of the 50 most recent intake events, newest first. Each object contains:
            - id (string): Unique identifier of the lifestyle event.
            - name (string): Recorded food or drink name.
            - calories (number): Calories recorded for that event.
        """
        events = self.core_client.get_lifestyle_events(event_type="intake")
        self._verify_error_response(events)

        return [self._extract_event(event) for event in events]

    def record_lifestyle_intake(
        self, content: str, calories: float, timestamp: int | None = None
    ) -> dict[str, Any]:
        """Records a food or drink intake event in Travel Portal.
        Use this to record food or drink intake reported by the user (e.g. "a chicken sandwich", "coffee with milk").

        Args:
            content: Concise name for the food or drink (e.g. "Chicken sandwich", "Coffee with milk").
            calories: Calories to record (e.g. 450). Use the matched history value when applicable, otherwise provide the language model's reasonable estimate. The stored value is made non-negative.
            timestamp: Optional Unix epoch timestamp in seconds for when the intake occurred. Convert user-provided natural-language dates or times with the time tools (e.g. use the user's specified lunch time). Omit to record the event now.

        Returns:
            The created lifestyle event object containing:
            - id (string): Unique identifier of the lifestyle event.
            - name (string): Recorded food or drink name.
            - calories (number): Calories recorded for the event.
        """
        return self._record_event(
            event_type="intake",
            content=content,
            calories=calories,
            timestamp=timestamp,
        )

    def record_lifestyle_activity(
        self, content: str, calories: float, timestamp: int | None = None
    ) -> dict[str, Any]:
        """Records manually reported physical activity expenditure in calories.
        Use this to record physical activity reported by the user (e.g. "30-minute run", "walked the dog").

        Args:
            content: Concise description of the activity, based on what the user reported (e.g. "30-minute run").
            calories: Calories expended during the activity (e.g. 250). Use a value given by the user or estimate it from the reported activity. The stored value is made non-negative.
            timestamp: Optional Unix epoch timestamp in seconds for when the activity occurred. Convert user-provided natural-language dates or times with the time tools (e.g. the user's specified workout time). Omit to record the event now.

        Returns:
            The created lifestyle event object containing:
            - id (string): Unique identifier of the lifestyle event.
            - name (string): Recorded physical activity description.
            - calories (number): Calories recorded for the event.
        """
        return self._record_event(
            event_type="expense",
            content=content,
            calories=calories,
            timestamp=timestamp,
        )

    def delete_lifestyle_event(self, lifestyle_event_id: str) -> dict[str, Any]:
        """Deletes a lifestyle event from Travel Portal.
        Use this when the user asks to delete, remove, or discard a recorded food or drink intake or physical activity (e.g. "delete the lunch I just logged").
        Resolve the event ID from the created event object or lifestyle event history first. Never ask the user for a UUID.

        Args:
            lifestyle_event_id: Unique identifier of the lifestyle event to delete. Resolve it from an existing event; never invent it.

        Returns:
            A confirmation object indicating successful deletion containing:
            - deleted (boolean): True if deletion succeeded.
            - id (string): Unique identifier of the deleted lifestyle event.
        """
        result = self.core_client.delete_lifestyle_event(
            lifestyle_event_id=lifestyle_event_id
        )
        self._verify_error_response(result)

        return {"deleted": True, "id": lifestyle_event_id}

    def _record_event(
        self,
        event_type: str,
        content: str,
        calories: float,
        timestamp: int | None,
    ) -> dict[str, Any]:
        event = self.core_client.create_lifestyle_event(
            event_type=event_type,
            timestamp=int(
                timestamp if timestamp is not None else datetime.now().timestamp()
            ),
            content=content,
            calories=abs(calories),
        )
        self._verify_error_response(event)

        return self._extract_event(event)
