<?php
    namespace Core\Service\Place;

    enum PlaceIncludedEntity : string {
        case Excerpt = "EXCERPT";
        case Categories = "CATEGORIES";
        case Highlights = "HIGHLIGHTS";
        case Labels = "LABELS";
        case Dates = "DATES";
        case NearbyPlaces = "NERBY_PLACES";
        case Notes = "NOTES";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>