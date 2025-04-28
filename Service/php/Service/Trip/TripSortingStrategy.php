<?php

    namespace Service\Service\Trip;

    enum TripSortingStrategy : string {
        case StartAscending = "ORDER BY start ASC";
        case LongestAscending = "ORDER BY (end - start) DESC";
        case ShortestAscending = "ORDER BY (end - start) ASC";
    }
?>