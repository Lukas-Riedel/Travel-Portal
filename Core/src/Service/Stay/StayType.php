<?php
    namespace Core\Service\Stay;

    use Core\Client\Calendar\Calendar;
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StayType",
        type: "string",
        description: "An enum representing a stay type"
    )]
    enum StayType : string {
        case Scheduled = "scheduled";
        case Watched = "watched";

        public function getTableName() : string {
            return match ($this) {
                self::Scheduled => "stay_event",
                self::Watched => "stay_watched_event"
            };
        }

        public function getCalendar() : Calendar {
            return match ($this) {
                self::Scheduled => Calendar::Stays,
                self::Watched => Calendar::WatchedStays
            };
        }
    }
?>
