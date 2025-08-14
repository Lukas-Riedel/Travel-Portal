<?php
    namespace Core\Service\Category;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "CategoryCategory",
        type: "string",
        description: "The category of the category"
    )]
    enum CategoryCategory : string {
        case Continent = "CONTINENT";
        case Country = "COUNTRY";
        case Administrative = "ADMINISTRATIVE";
        case Ocean = "OCEAN";
        case Sea = "SEA";
        case Bay = "BAY";
        // TODO: Delete.
        case Variable = "VARIABLE";
        case Island = "ISLAND";
        case Region = "REGION";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>