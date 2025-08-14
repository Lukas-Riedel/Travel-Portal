<?php
    namespace Core\Service\Place;

    enum PlaceSortingStrategy : string {
        case Default = "ORDER BY pe.start ASC";
        case ScoreDescending = "ORDER BY pi.score DESC";
        case QualityAscending = "ORDER BY pi.quality ASC";
        case LatitudeAscending = "ORDER BY pi.latitude ASC, pe.start ASC";
        case LatitudeDescending = "ORDER BY pi.latitude DESC, pe.start ASC";
        case LongitudeAscending = "ORDER BY pi.longitude ASC, pe.start ASC";
        case LongitudeDescending = "ORDER BY pi.longitude DESC, pe.start ASC";
    }
?>