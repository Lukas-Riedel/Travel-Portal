<?php
    namespace Core\Service\Flight;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "FlightSortingStrategy",
        type: "string",
        description: "The sorting strategy of the flights"
    )]
    enum FlightSortingStrategy : string {
        case ScheduledDepartureTimeAscending = "scheduledDeparture";
        case ScheduledDepartureTimeDescending = "-scheduledDeparture";
        case DurationAscending = "duration";
        case DurationDescending = "-duration";
        case DelayAscending = "delay";   
        case DelayDescending = "-delay";        
        
        public function getOrderByClause() : string {
            return match ($this) {
                self::ScheduledDepartureTimeAscending => "ORDER BY fl.actual_departure ASC",
                self::ScheduledDepartureTimeDescending => "ORDER BY fl.actual_departure DESC",
                self::DurationAscending => "ORDER BY (fl.actual_arrival - fl.actual_departure) ASC",
                self::DurationDescending => "ORDER BY (fl.actual_arrival - fl.actual_departure) DESC",
                self::DelayAscending => "ORDER BY (fl.actual_arrival - fe.end) ASC",
                self::DelayDescending => "ORDER BY (fl.actual_arrival - fe.end) DESC"
            };
        }
    }
?>