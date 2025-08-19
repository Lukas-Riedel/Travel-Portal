<?php
    namespace Core\Service\Trip;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TripIncludedEntity",
        type: "string",
        description: "The entity of the trip"
    )]    
    enum TripIncludedEntity : string {
        case Expenses = "EXPENSES";
        case Stays = "STAYS";
        case Flights = "FLIGHTS";
        case WatchedFlights = "WATCHED_FLIGHTS";
        case Fitness = "FITNESS";
        case Notes = "NOTES";
        case Highlights = "HIGHLIGHTS";
        case Statistics = "STATISTICS";
        case PublicHolidays = "PUBLIC_HOLIDAYS";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>