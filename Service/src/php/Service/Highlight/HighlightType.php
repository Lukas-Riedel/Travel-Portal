<?php
    namespace Service\Service\Highlight;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "HighlightType",
        type: "string",
        description: "An enum representing a highlight type"
    )]
    enum HighlightType : string {
        case Place = "place";
        case Trip = "trip";
        case Category = "category";
        case Year = "year";

        public function getTableName() : string {
            return match ($this) {
                self::Place => "highlight_place",
                self::Trip => "highlight_trip",
                self::Category => "highlight_category",
                self::Year => "highlight_year"
            };
        }
    }
?>