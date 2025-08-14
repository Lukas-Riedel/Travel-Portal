<?php
    namespace Core\Service\Place;

    enum SpecialPlaceType {
        case Candidate;
        case Permanent;

        public function getTableName() : string {
            return match ($this) {
                self::Candidate => "place_candidate",
                self::Permanent => "place_permanent"
            };
        }
    }
?>