<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StatisticsUnit",
        type: "string",
        description: "The unit of the statistics record"
    )]
    enum StatisticsUnit : string {
        case Kilometers = "kilometers";
        case Duration = "duration";
        case Photos = "photos";
        case Countries = "countries";
        case Places = "places";
        case MainCurrency = "mainCurrency";
        case Days = "days";
        case Flights = "flights";
        case Steps = "steps";
        case BeforeDaysTimestamp = "beforeDaysTimestamp";
        case Visits = "visits";
        case Airports = "airports";
        case Nights = "nights";
        case Latitude = "latitude";
        case Longitude = "longitude";
    }
?>