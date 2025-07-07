<?php
    namespace Service\Service\Place;

    enum PlaceSortingStrategy : string {
        case Default = "ORDER BY start ASC";
        case ScoreDescending = "ORDER BY score DESC";
        case QualityAscending = "ORDER BY quality ASC";
        case DistanceFromHomeDescending = "ORDER BY GET_DISTANCE(latitude, longitude, ?, ?) DESC, start ASC";
        case LatitudeAscending = "ORDER BY latitude ASC, start ASC";
        case LatitudeDescending = "ORDER BY latitude DESC, start ASC";
        case LongitudeAscending = "ORDER BY longitude ASC, start ASC";
        case LongitudeDescending = "ORDER BY longitude DESC, start ASC";
    }
?>