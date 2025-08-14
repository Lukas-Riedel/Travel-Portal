<?php
    namespace Core\Service\Flight;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "FlightType",
        type: "string",
        description: "An enum representing a flight type"
    )]
    enum FlightType : string {
        case Scheduled = "scheduled";
        case Watched = "watched";
        case Logged = "logged";

        public function getTableName() : ?string {
            return match ($this) {
                self::Scheduled => "flight_event",
                self::Watched => "flight_watched_event",
                self::Logged => NULL
            };
        }

        public function getCalendar() : ?\Calendar {
            return match ($this) {
                self::Scheduled => \Calendar::Flights,
                self::Watched => \Calendar::WatchedFlights,
                self::Logged => NULL
            };
        }
    }
?>