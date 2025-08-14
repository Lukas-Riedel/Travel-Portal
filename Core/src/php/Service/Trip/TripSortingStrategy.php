<?php

    namespace Core\Service\Trip;

    enum TripSortingStrategy : string {
        case Default = "ORDER BY start ASC";
        case LongestAscending = "ORDER BY (end - start) DESC";
        case ShortestAscending = "ORDER BY (end - start) ASC";
    }
?>