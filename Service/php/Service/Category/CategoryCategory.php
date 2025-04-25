<?php
    namespace Service\Service\Category;
    
    enum CategoryCategory : string {
        case Continent = "CONTINENT";
        case Country = "COUNTRY";
        case Administrative = "ADMINISTRATIVE";
        case Ocean = "OCEAN";
        case Sea = "SEA";
        case Bay = "BAY";
        case Variable = "VARIABLE";
        case Island = "ISLAND";
        case Region = "REGION";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>