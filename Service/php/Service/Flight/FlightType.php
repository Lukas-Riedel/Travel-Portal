<?php
    namespace Service\Service\Flight;

    enum FlightType {
        case Scheduled;
        case Watched;

        public function getTableName() : string {
            return match ($this) {
                self::Scheduled => "flight_event",
                self::Watched => "flight_watched_event"
            };
        }

        public function getCalendar() : \Calendar {
            return match ($this) {
                self::Scheduled => \Calendar::Flights,
                self::Watched => \Calendar::WatchedFlights
            };
        }
    }
?>