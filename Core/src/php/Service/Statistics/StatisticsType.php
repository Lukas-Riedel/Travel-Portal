<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StatisticsType",
        type: "string",
        description: "The type of the statistics record"
    )]
    enum StatisticsType : string {
        case Overall = "ALL";
        case Trip = "TRIP";
        case Category = "CATEGORY";
        case Year = "YEAR";
    }
?>