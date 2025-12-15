<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "PlaceSortingStrategy",
        type: "string",
        description: "The sorting strategy of the palces"
    )]
    enum PlaceSortingStrategy : string {
        case OldestAscending = "oldest";
        case OldestDescending = "-oldest";
        case ScoreAsscending = "score";
        case ScoreDescending = "-score";
        case QualityAscending = "quality";
        case QualityDescending = "-quality";
        case LatitudeAscending = "latitude";
        case LatitudeDescending = "-latitude";
        case LongitudeAscending = "longitude";
        case LongitudeDescending = "-longitude";

        public function getOrderByClause() : string {
            return match ($this) {
                self::OldestAscending => "ORDER BY pe.\"start\" ASC NULLS LAST",
                self::OldestDescending => "ORDER BY pe.\"start\" DESC NULLS LAST",
                self::ScoreAsscending => "ORDER BY pi.score ASC",
                self::ScoreDescending => "ORDER BY pi.score DESC",
                self::QualityAscending => "ORDER BY pi.quality ASC",
                self::QualityDescending => "ORDER BY pi.quality DESC",
                self::LatitudeAscending => "ORDER BY pi.latitude ASC, pe.\"start\" ASC",
                self::LatitudeDescending => "ORDER BY pi.latitude DESC, pe.\"start\" ASC",
                self::LongitudeAscending => "ORDER BY pi.longitude ASC, pe.\"start\" ASC",
                self::LongitudeDescending => "ORDER BY pi.longitude DESC, pe.\"start\" ASC"
            };
        }
    }
?>