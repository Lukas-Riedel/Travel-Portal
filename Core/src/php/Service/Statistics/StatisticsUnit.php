<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StatisticsUnit",
        type: "string",
        description: "The unit of the statistics record"
    )]
    enum StatisticsUnit : string {
        case Kilometers = "KILOMETERS";
        case Duration = "DURATION";
        case Photos = "PHOTOS";
        case Countries = "COUNTRIES";
        case Places = "PLACES";
        case MainCurrency = "MAIN_CURRENCY";
        case Days = "DAYS";
        case Flights = "FLIGHTS";
        case Steps = "STEPS";
        case BeforeDaysTimestamp = "BEFORE_DAYS_TIMESTAMP";
        case Visits = "VISITS";
        case Airports = "AIRPORTS";
        case Nights = "NIGHTS";
    }
?>