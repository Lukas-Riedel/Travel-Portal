<?php
    namespace Service\Service\TimeTracking;

    enum TimeTrackingEventType : string {
        case Vacation = "vacation";
        case Selfcare = "selfcare";
        case Tenure = "tenure";
        case Overtime = "overtime";
        case PlannedWork = 'plannedWork';
    }
?>