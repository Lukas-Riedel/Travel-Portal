<?php
    namespace Service\Service\TimeTracking;

    enum TimeTrackingEventType : string {
        case Vacation = "VACATION";
        case Selfcare = "SELFCARE";
        case Tenure = "TENURE";
        case Overtime = "OVERTIME";
    }
?>