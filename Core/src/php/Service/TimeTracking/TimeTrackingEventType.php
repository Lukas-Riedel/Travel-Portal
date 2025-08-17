<?php
    namespace Core\Service\TimeTracking;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TimeTrackingEventType",
        type: "string",
        description: "The type of the time tracking event"
    )]
    enum TimeTrackingEventType : string {
        case Vacation = "vacation";
        case Selfcare = "selfcare";
        case Tenure = "tenure";
        case Overtime = "overtime";
        case PlannedWork = 'plannedWork';
    }
?>