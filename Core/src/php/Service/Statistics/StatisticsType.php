<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StatisticsType",
        type: "string",
        description: "The type of the statistics record"
    )]
    enum StatisticsType : string {
        case Overall = "overall";
        case Trip = "trip";
        case Category = "category";
        case Year = "year";
    }
?>