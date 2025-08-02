<?php
    namespace Service\Service\Highlight;

    enum HighlightType {
        case Place;
        case Trip;
        case Category;
        case Year;

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