<?php

    namespace Core\Service\Fitness;

    enum FitnessSortingStrategy : string {
        case StepsAscending = "ORDER BY steps ASC";
        case StepsDescending = "ORDER BY steps DESC";
        case TimeInMotionAscending = "ORDER BY seconds ASC";
        case TimeInMotionDescending = "ORDER BY seconds DESC";
    }
?>