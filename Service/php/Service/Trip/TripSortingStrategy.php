<?php

    namespace Service\Service\Trip;

    enum TripSortingStrategy : string {
        case Default = "ORDER BY start ASC";
        case LongestAscending = "ORDER BY (end - start) DESC";
        case ShortestAscending = "ORDER BY (end - start) ASC";
        case CostDescending = "ORDER BY cost DESC";
        case CostAscending = "ORDER BY cost ASC";
        case CostPerDayDescending = "ORDER BY (cost / days) DESC";
        case CostPerDayAscending = "ORDER BY (cost / days) ASC";
    }
?>