import time
from abc import ABC
from datetime import tzinfo
from typing import Any

import pytz

from src.tools.base_tools import BaseCoreTools


class BaseFlightTools(BaseCoreTools, ABC):
    def _get_airport_timezone(self, timezone_name: str | None) -> tzinfo:
        if not timezone_name:
            return pytz.UTC

        return pytz.timezone(timezone_name)

    def _extract_flight(
        self,
        flight: dict[str, Any],
        trip: dict[str, Any] | None,
    ) -> dict[str, Any]:
        from_airport = flight.get("from")
        to_airport = flight.get("to")
        airline = flight.get("airline") or {}

        from_tz = self._get_airport_timezone(from_airport.get("timezone"))
        to_tz = self._get_airport_timezone(to_airport.get("timezone"))

        start = flight.get("start")
        end = flight.get("end")

        return {
            "flight": flight.get("flight"),
            "trip_id": trip.get("id") if trip else None,
            "trip_name": f"{trip.get('name')} {trip.get('year')}" if trip else None,
            "airline": airline.get("name"),
            "aircraft": flight.get("aircraft"),
            "registration": flight.get("registration"),
            "from": {
                "name": from_airport.get("longName") or from_airport.get("shortName"),
                "code": from_airport.get("code"),
                "country": from_airport.get("country"),
            },
            "to": {
                "name": to_airport.get("longName") or to_airport.get("shortName"),
                "code": to_airport.get("code"),
                "country": to_airport.get("country"),
            },
            "start_date": self._epoch_to_date(
                epoch=start,
                user_tz=from_tz,
            ),
            "start_time": self._epoch_to_time(
                epoch=start,
                user_tz=from_tz,
            ),
            "end_date": self._epoch_to_date(
                epoch=end,
                user_tz=to_tz,
            ),
            "end_time": self._epoch_to_time(
                epoch=end,
                user_tz=to_tz,
            ),
            "distance": flight.get("distance"),
            "duration": end - start,
        }

    def _extract_watched_flight(
        self,
        flight: dict[str, Any],
        trip: dict[str, Any] | None,
    ) -> dict[str, Any]:
        from_airport = flight.get("from")
        to_airport = flight.get("to")

        from_tz = self._get_airport_timezone(from_airport.get("timezone"))
        to_tz = self._get_airport_timezone(to_airport.get("timezone"))

        start = flight.get("start")
        end = flight.get("end")

        return {
            "flight": flight.get("flight"),
            "trip_id": trip.get("id") if trip else None,
            "trip_name": f"{trip.get('name')} {trip.get('year')}" if trip else None,
            "from": {
                "name": from_airport.get("shortName"),
            },
            "to": {
                "name": to_airport.get("shortName"),
            },
            "start_date": self._epoch_to_date(
                epoch=start,
                user_tz=from_tz,
            ),
            "start_time": self._epoch_to_time(
                epoch=start,
                user_tz=from_tz,
            ),
            "end_date": self._epoch_to_date(
                epoch=end,
                user_tz=to_tz,
            ),
            "end_time": self._epoch_to_time(
                epoch=end,
                user_tz=to_tz,
            ),
            "duration": end - start,
        }


class TripFlightReadTools(BaseFlightTools):
    def _get_description(self) -> str | None:
        return "Provides capabilities to query and inspect scheduled, watched, and non-logged flights across trips in Travel Portal. Activate and apply this skill when the user asks about flights, bookings, flight schedules, airlines flown with, airports, tracked/watched flights, or flights that still need to be logged for a specific trip or across all trips."

    def _get_supported_skill(self) -> str:
        return "trip.flight.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_scheduled_flights,
            self.get_watched_flights,
            self.get_non_logged_flights,
        ]

    def get_scheduled_flights(
        self,
        trip_id: str | None,
        year: int | None,
        airline: str | None,
        airport: str | None,
        aircraft: str | None,
    ) -> list[dict[str, Any]]:
        """Retrieves scheduled flights, optionally filtered by trip, year, airline, airport, or aircraft type.
        Use this when the user asks about flights, booked flights, flight schedule, flights with a specific airline, departing/arriving at an airport, or flown on a specific aircraft type (e.g. "what flights do I have booked?", "show flights for my Japan trip", "did I fly with Emirates in 2024?", "list flights from Prague", "show all flights on A380").
        Returns an empty list if no scheduled flights match the criteria.

        Args:
            trip_id: Filter flights to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve flights across all regular trips.
            year: Filter flights to a specific calendar year (e.g. 2024). Pass None to retrieve flights across all years.
                Ignored when trip_id is provided.
            airline: Filter flights to a specific airline by name or code (e.g. "Emirates", "EK", "Ryanair", "FR"). Pass None to skip airline filtering.
            airport: Filter flights by departure or arrival airport name or IATA code (e.g. "PRG", "Prague", "DXB", "Dubai"). Pass None to skip airport filtering.
            aircraft: Filter flights by aircraft type code (e.g. "A388", "B738", "A320"). Pass None to skip aircraft filtering.
        Returns:
            An empty list if no flights exist, or a list of flight objects where each object contains:
            - flight (string): Flight number (e.g. `"EK139"`).
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name and year of the parent trip (e.g. `"Japan 2025"`).
            - airline (string | null): Name of the operating airline (e.g. `"Emirates"`), or null if unknown (the flight is in the future).
            - aircraft (string | null): Aircraft type code (e.g. `"A388"`), or null if unknown (the flight is in the future).
            - registration (string | null): Aircraft registration code (e.g. `"A6-EOQ"`), or null if unknown (the flight is in the future).
            - from (object): Departure airport details, containing:
                - name (string): Name of the departure airport (e.g. `"Václav Havel Prague Airport"`).
                - code (string | null): IATA code of the departure airport (e.g. `"PRG"`).
                - country (string | null): Country of the departure airport (e.g. `"Czechia"`).
            - to (object): Arrival airport details, containing:
                - name (string): Name of the arrival airport (e.g. `"Dubai International Airport"`).
                - code (string | null): IATA code of the arrival airport (e.g. `"DXB"`).
                - country (string | null): Country of the arrival airport (e.g. `"United Arab Emirates"`).
            - start_date (string): Scheduled departure date in the origin airport's local timezone, in DD.MM.YYYY format (e.g. `"05.07.2025"`).
            - start_time (string): Scheduled departure time in the origin airport's local timezone, in HH:MM format (e.g. `"14:00"`).
            - end_date (string): Scheduled arrival date in the destination airport's local timezone, in DD.MM.YYYY format (e.g. `"06.07.2025"`).
            - end_time (string): Scheduled arrival time in the destination airport's local timezone, in HH:MM format (e.g. `"06:00"`).
            - distance (number | null): Flight distance in kilometers (e.g. `4450.5`).
            - duration (integer): Duration of the flight in seconds (e.g. `28800`).
        """
        if airline:
            search_results = self.core_client.search(
                query=airline.strip(),
                include="airline",
            )
            self._verify_error_response(search_results)

            airline_ids = {
                r.get("entity", {}).get("id")
                for r in search_results
                if r.get("type") == "airline" and (r.get("entity") or {}).get("id")
            }
            if not airline_ids:
                return []
        else:
            airline_ids = None

        if airport:
            search_results = self.core_client.search(
                query=airport.strip(),
                include="airport",
            )
            self._verify_error_response(search_results)

            airport_ids = {
                r.get("entity", {}).get("id")
                for r in search_results
                if r.get("type") == "airport" and (r.get("entity") or {}).get("id")
            }
            if not airport_ids:
                return []
        else:
            airport_ids = None

        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id=trip_id)
            self._verify_error_response(trip)
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="flights",
                year=year,
            )
            self._verify_error_response(trips)

        aircraft_query = aircraft.strip().lower() if aircraft else None
        matched_flights = []

        for current_trip in trips:
            if not self._is_trip_visible(current_trip):
                continue

            for flight in current_trip.get("flights", []):
                if airline_ids is not None:
                    flight_airline_id = (flight.get("airline") or {}).get("id")
                    if flight_airline_id not in airline_ids:
                        continue

                if airport_ids is not None:
                    from_id = (flight.get("from") or {}).get("id")
                    to_id = (flight.get("to") or {}).get("id")
                    if from_id not in airport_ids and to_id not in airport_ids:
                        continue

                if aircraft_query:
                    flight_aircraft = (flight.get("aircraft") or "").lower()
                    if aircraft_query != flight_aircraft:
                        continue

                matched_flights.append((flight, current_trip))

        return [
            self._extract_flight(
                flight=flight,
                trip=current_trip,
            )
            for flight, current_trip in matched_flights
        ]

    def get_watched_flights(
        self,
        trip_id: str | None,
        year: int | None,
    ) -> list[dict[str, Any]]:
        """Retrieves watched flights (flights being monitored or tracked for a trip), optionally filtered by trip or year.
        Use this when the user asks about watched or monitored flights (e.g. "what flights am I watching?", "show watched flights for my next trip", "list all watched flights this year").
        Returns an empty list if no watched flights match the criteria.

        Args:
            trip_id: Filter watched flights to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve watched flights across all regular trips.
            year: Filter watched flights to a specific calendar year (e.g. 2024). Pass None to retrieve watched flights across all years.
                Ignored when trip_id is provided.
        Returns:
            An empty list if no flights exist, or a list of flight objects where each object contains:
            - flight (string): Flight number (e.g. `"EK139"`).
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name and year of the parent trip (e.g. `"Japan 2025"`).
            - from (object): Departure airport details, containing:
                - name (string): City of the departure airport (e.g. `"Prague"`).
            - to (object): Arrival airport details, containing:
                - name (string): City of the arrival airport (e.g. `"Dubai"`).
            - start_date (string): Scheduled departure date in the origin airport's local timezone, in DD.MM.YYYY format (e.g. `"05.07.2025"`).
            - start_time (string): Scheduled departure time in the origin airport's local timezone, in HH:MM format (e.g. `"14:00"`).
            - end_date (string): Scheduled arrival date in the destination airport's local timezone, in DD.MM.YYYY format (e.g. `"06.07.2025"`).
            - end_time (string): Scheduled arrival time in the destination airport's local timezone, in HH:MM format (e.g. `"06:00"`).
            - duration (integer): Duration of the flight in seconds (e.g. `28800`).
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id=trip_id)
            self._verify_error_response(trip)
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="watchedFlights",
                year=year,
            )
            self._verify_error_response(trips)

        matched_flights = []

        for current_trip in trips:
            if not self._is_trip_visible(current_trip):
                continue

            for flight in current_trip.get("watchedFlights", []):
                matched_flights.append((flight, current_trip))

        return [
            self._extract_watched_flight(
                flight=flight,
                trip=current_trip,
            )
            for flight, current_trip in matched_flights
        ]

    def get_non_logged_flights(self) -> list[dict[str, Any]]:
        """Retrieves flights that have already departed but have not been logged yet, oldest first.
        Use this when the user asks about flights that were not logged, flights waiting to be logged, or flights with missing flight details
        (e.g. "which flights have I not logged yet?", "do I have any flights to log?").

        Returns:
            An empty list if no flights exist, or a list of flight objects where each object contains:
            - flight (string): Flight number (e.g. `"EK139"`).
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name and year of the parent trip (e.g. `"Japan 2025"`).
            - from (object): Departure airport details, containing:
                - name (string): City of the departure airport (e.g. `"Prague"`).
            - to (object): Arrival airport details, containing:
                - name (string): City of the arrival airport (e.g. `"Dubai"`).
            - start_date (string): Scheduled departure date in the origin airport's local timezone, in DD.MM.YYYY format (e.g. `"05.07.2025"`).
            - start_time (string): Scheduled departure time in the origin airport's local timezone, in HH:MM format (e.g. `"14:00"`).
            - end_date (string): Scheduled arrival date in the destination airport's local timezone, in DD.MM.YYYY format (e.g. `"06.07.2025"`).
            - end_time (string): Scheduled arrival time in the destination airport's local timezone, in HH:MM format (e.g. `"06:00"`).
            - duration (integer): Duration of the flight in seconds (e.g. `28800`).
        """
        trips = self.core_client.get_trips(trip_type="regular", include="flights")
        self._verify_error_response(trips)

        latest_start = min(int(time.time()), self._get_maximum_timestamp())
        non_logged_flights = []

        for trip in trips:
            if not self._is_trip_visible(trip):
                continue

            for flight in trip.get("flights", []):
                is_logged = flight.get("registration") is not None
                if not is_logged and flight["start"] < latest_start:
                    non_logged_flights.append((flight, trip))

        non_logged_flights.sort(key=lambda item: item[0]["start"])

        return [
            self._extract_flight(
                flight=flight,
                trip=trip,
            )
            for flight, trip in non_logged_flights
        ]


class FlightEditTools(BaseFlightTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create scheduled and watched flights and to log flights that have already departed in Travel Portal. Activate and apply this skill when the user asks to add, record, watch, schedule, or log a flight (e.g. "add a flight from Prague to Bangkok on May 5", "watch flight FR1234 on December 11", "schedule a flight to Lisbon for my Portugal trip", "log my flight EK139").'

    def _get_supported_skill(self) -> str:
        return "flight.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_scheduled_flight,
            self.create_watched_flight,
            self.log_flight,
        ]

    def create_scheduled_flight(
        self,
        flight: str,
        origin_airport_name: str,
        destination_airport_name: str,
        scheduled_departure: int,
        scheduled_arrival: int,
    ) -> dict[str, Any]:
        """Creates a new scheduled flight in Travel Portal.
        Use this when the user asks to add, record, or schedule a booked flight (e.g. "add flight EK139 from Prague to Dubai on July 5th", "create a scheduled flight LH1234").

        Args:
            flight: Flight number (e.g. "EK139", "BA854").
            origin_airport_name: Departure airport city or name (e.g. "Prague", "Dubai").
            destination_airport_name: Arrival airport city or name (e.g. "Dubai", "London").
            scheduled_departure: Scheduled departure time as Unix epoch timestamp in seconds. Use time conversion tools if needed.
            scheduled_arrival: Scheduled arrival time as Unix epoch timestamp in seconds. Use time conversion tools if needed.

        Returns:
            The created flight object containing:
            - flight (string): Flight number (e.g. `"EK139"`).
            - from (object): Departure airport details, containing:
                - name (string): City of the departure airport (e.g. `"Václav Havel Prague Airport"`).
            - to (object): Arrival airport details, containing:
                - name (string): City of the arrival airport (e.g. `"Dubai International Airport"`).
            - start_date (string): Scheduled departure date in the origin airport's local timezone, in DD.MM.YYYY format (e.g. `"05.07.2025"`).
            - start_time (string): Scheduled departure time in the origin airport's local timezone, in HH:MM format (e.g. `"14:00"`).
            - end_date (string): Scheduled arrival date in the destination airport's local timezone, in DD.MM.YYYY format (e.g. `"06.07.2025"`).
            - end_time (string): Scheduled arrival time in the destination airport's local timezone, in HH:MM format (e.g. `"06:00"`).
            - duration (integer): Duration of the flight in seconds (e.g. `28800`).
        """
        result = self.core_client.create_flight(
            flight_type="scheduled",
            flight=flight,
            origin_airport_name=origin_airport_name,
            destination_airport_name=destination_airport_name,
            scheduled_departure=scheduled_departure,
            scheduled_arrival=scheduled_arrival,
        )
        self._verify_error_response(result)

        return self._extract_flight(
            flight=result,
            trip=None,
        )

    def create_watched_flight(
        self,
        flight: str,
        origin_airport_name: str,
        destination_airport_name: str,
        scheduled_departure: int,
        scheduled_arrival: int,
    ) -> dict[str, Any]:
        """Creates a new watched flight to be tracked or monitored in Travel Portal.
        Use this when the user asks to watch or track a flight (e.g. "track flight BA854 from London to Prague", "watch flight LH1234").

        Args:
            flight: Flight number (e.g. "EK139", "BA854").
            origin_airport_name: Departure airport city or name (e.g. "Prague", "London").
            destination_airport_name: Arrival airport city or name (e.g. "Dubai", "Prague").
            scheduled_departure: Scheduled departure time as Unix epoch timestamp in seconds. Use time conversion tools if needed.
            scheduled_arrival: Scheduled arrival time as Unix epoch timestamp in seconds. Use time conversion tools if needed.

        Returns:
            The created watched flight object containing:
            - flight (string): Flight number (e.g. `"EK139"`).
            - from (object): Departure airport details, containing:
                - name (string): City of the departure airport (e.g. `"Václav Havel Prague Airport"`).
            - to (object): Arrival airport details, containing:
                - name (string): City of the arrival airport (e.g. `"Dubai International Airport"`).
            - start_date (string): Scheduled departure date in the origin airport's local timezone, in DD.MM.YYYY format (e.g. `"05.07.2025"`).
            - start_time (string): Scheduled departure time in the origin airport's local timezone, in HH:MM format (e.g. `"14:00"`).
            - end_date (string): Scheduled arrival date in the destination airport's local timezone, in DD.MM.YYYY format (e.g. `"06.07.2025"`).
            - end_time (string): Scheduled arrival time in the destination airport's local timezone, in HH:MM format (e.g. `"06:00"`).
            - duration (integer): Duration of the flight in seconds (e.g. `28800`).
        """
        result = self.core_client.create_flight(
            flight_type="watched",
            flight=flight,
            origin_airport_name=origin_airport_name,
            destination_airport_name=destination_airport_name,
            scheduled_departure=scheduled_departure,
            scheduled_arrival=scheduled_arrival,
        )
        self._verify_error_response(result)

        return self._extract_watched_flight(
            flight=result,
            trip=None,
        )

    def log_flight(
        self,
        flight: str,
        origin_airport_name: str,
        origin_airport_code: str,
        destination_airport_name: str,
        destination_airport_code: str,
        aircraft: str,
        registration: str,
        scheduled_departure: int,
        actual_departure: int,
        scheduled_arrival: int,
        actual_arrival: int,
    ) -> dict[str, Any]:
        """Logs a flight that has already departed with its full flight details in Travel Portal.
        Use this when the user asks to log a flight returned by get_non_logged_flights and has provided all the flight details.
        All parameters are required. Never invent or guess any of them, ask the user for the missing ones.

        Args:
            flight: Flight number of the non-logged flight (e.g. "EK139").
            origin_airport_name: Departure airport name exactly as returned by get_non_logged_flights (e.g. "Prague").
            origin_airport_code: IATA code of the departure airport, exactly 3 uppercase letters (e.g. "PRG").
            destination_airport_name: Arrival airport name exactly as returned by get_non_logged_flights (e.g. "Dubai").
            destination_airport_code: IATA code of the arrival airport, exactly 3 uppercase letters (e.g. "DXB").
            aircraft: Aircraft type code (e.g. "A388", "B738").
            registration: Aircraft registration code (e.g. "A6-EOQ").
            scheduled_departure: Scheduled departure as Unix epoch timestamp in seconds. Compute it from start_date and start_time of the flight returned by get_non_logged_flights, which are in the departure airport's local time. Use the airport's timezone and time conversion tools.
            actual_departure: Actual departure as Unix epoch timestamp in seconds. The user gives it in the departure airport's local time, convert it using the airport's timezone and time conversion tools.
            scheduled_arrival: Scheduled arrival as Unix epoch timestamp in seconds. Compute it from end_date and end_time of the flight returned by get_non_logged_flights, which are in the arrival airport's local time. Use the airport's timezone and time conversion tools.
            actual_arrival: Actual arrival as Unix epoch timestamp in seconds. The user gives it in the arrival airport's local time, convert it using the airport's timezone and time conversion tools.

        Returns:
            The logged flight object containing:
            - flight (string): Flight number (e.g. `"EK139"`).
            - airline (string | null): Name of the operating airline (e.g. `"Emirates"`).
            - aircraft (string): Aircraft type code (e.g. `"A388"`).
            - registration (string): Aircraft registration code (e.g. `"A6-EOQ"`).
            - from (object): Departure airport details, containing:
                - name (string): Name of the departure airport (e.g. `"Václav Havel Prague Airport"`).
                - code (string): IATA code of the departure airport (e.g. `"PRG"`).
                - country (string): Country of the departure airport (e.g. `"Czechia"`).
            - to (object): Arrival airport details, containing:
                - name (string): Name of the arrival airport (e.g. `"Dubai International Airport"`).
                - code (string): IATA code of the arrival airport (e.g. `"DXB"`).
                - country (string): Country of the arrival airport (e.g. `"United Arab Emirates"`).
            - start_date (string): Actual departure date in the origin airport's local timezone, in DD.MM.YYYY format (e.g. `"05.07.2025"`).
            - start_time (string): Actual departure time in the origin airport's local timezone, in HH:MM format (e.g. `"14:00"`).
            - end_date (string): Actual arrival date in the destination airport's local timezone, in DD.MM.YYYY format (e.g. `"06.07.2025"`).
            - end_time (string): Actual arrival time in the destination airport's local timezone, in HH:MM format (e.g. `"06:00"`).
            - distance (number): Flight distance in kilometers (e.g. `4450.5`).
            - duration (integer): Duration of the flight in seconds (e.g. `28800`).
        """
        result = self.core_client.log_flight(
            flight=flight,
            origin_airport_name=origin_airport_name,
            origin_airport_code=origin_airport_code,
            destination_airport_name=destination_airport_name,
            destination_airport_code=destination_airport_code,
            aircraft=aircraft,
            registration=registration,
            scheduled_departure=scheduled_departure,
            actual_departure=actual_departure,
            scheduled_arrival=scheduled_arrival,
            actual_arrival=actual_arrival,
        )
        self._verify_error_response(result)

        return self._extract_flight(
            flight=result,
            trip=None,
        )
