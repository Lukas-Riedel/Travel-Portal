<?php

    namespace Core\Service\Trip;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TripSortingStrategy",
        type: "string",
        description: "The sorting strategy of the trips"
    )]
    enum TripSortingStrategy : string {
        case OldestAscending = "oldest";
        case OldestDescending = "-oldest";
        case LongestAscending = "longest";
        case LongestDescending = "-longest";
        
        public function getOrderByClause() : string {
            return match ($this) {
                self::OldestAscending => "ORDER BY start ASC",
                self::OldestDescending => "ORDER BY start DESC",
                self::LongestAscending => "ORDER BY (end - start) DESC",
                self::LongestDescending => "ORDER BY (end - start) ASC"
            };
        }
    }
?>