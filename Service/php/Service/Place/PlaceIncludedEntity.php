<?php
    namespace Service\Service\Place;

    enum PlaceIncludedEntity : string {
        case Excerpt = "EXCERPT";
        case Categories = "CATEGORIES";
        case Highlights = "HIGHLIGHTS";
        case Labels = "LABELS";
        case Dates = "DATES";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>