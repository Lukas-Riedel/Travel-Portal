<?php
    namespace Core\Service\Category;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "CategoryCategory",
        type: "string",
        description: "The category of the category"
    )]
    enum CategoryCategory : string {
        case Continent = "continent";
        case Country = "country";
        case Administrative = "administrative";
        case Ocean = "ocean";
        case Sea = "sea";
        case Bay = "bay";
        case Island = "island";
        case Region = "region";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>