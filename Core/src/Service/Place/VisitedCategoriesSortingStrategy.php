<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "VisitedCategoriesSortingStrategy",
        type: "string",
        description: "The sorting strategy of the visited categories"
    )]
    enum VisitedCategoriesSortingStrategy : string {
        case TravelDaysCountAscending = "travelDays";
        case TravelDaysCountDescending = "-travelDays";
        case VisitedPlacesCountAscending = "visitedPlaces";
        case VisitedPlacesCountDescending = "-visitedPlaces";        
        
        public function getOrderByClause() : string {
            return match ($this) {
                self::TravelDaysCountAscending => "ORDER BY COUNT(DISTINCT p.start - (p.start % 86400)) DESC",
                self::VisitedPlacesCountAscending => "ORDER BY COUNT(DISTINCT p.place_id) DESC",
                self::TravelDaysCountDescending => "ORDER BY COUNT(DISTINCT p.start - (p.start % 86400)) DESC",
                self::VisitedPlacesCountDescending => "ORDER BY COUNT(DISTINCT p.place_id) DESC"
            };
        }
    }
?>