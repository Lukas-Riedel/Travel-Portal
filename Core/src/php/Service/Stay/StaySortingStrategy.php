<?php
    namespace Core\Service\Stay;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StaySortingStrategy",
        type: "string",
        description: "The sorting strategy of the stays"
    )]
    enum StaySortingStrategy : string {
        case DurationDescending = "-duration";
        case DurationAscending = "-duration";

        public function getOrderByClause() : string {
            return match ($this) {
                self::DurationDescending => "ORDER BY (end - start) DESC",
                self::DurationAscending => "ORDER BY (end - start) ASC"
            };
        }
    }
?>