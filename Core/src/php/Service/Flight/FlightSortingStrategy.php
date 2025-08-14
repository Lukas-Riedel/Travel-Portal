<?php
    namespace Core\Service\Flight;

    enum FlightSortingStrategy : string {
        case Default = "ORDER BY fl.actual_departure";
        case DurationDescending = "ORDER BY (fl.actual_arrival - fl.actual_departure) DESC";
        case DurationAscending = "ORDER BY (fl.actual_arrival - fl.actual_departure) ASC";
        case DelayDescending = "ORDER BY (fl.actual_arrival - fe.end) DESC";
    }
?>