<?php
    namespace Service\Service\Year;
    
    enum YearIncludedEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>