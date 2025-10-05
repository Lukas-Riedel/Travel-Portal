<?php
    namespace Core\Service\Trip;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TripIncludedEntity",
        type: "string",
        description: "The entity of the trip"
    )]    
    enum TripIncludedEntity : string {
        case Expenses = "expenses";
        case Stays = "stays";
        case Flights = "flights";
        case WatchedFlights = "watchedFlights";
        case Fitness = "fitness";
        case Notes = "notes";
        case Highlights = "highlights";
        case Statistics = "statistics";
        case PublicHolidays = "publicHolidays";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>