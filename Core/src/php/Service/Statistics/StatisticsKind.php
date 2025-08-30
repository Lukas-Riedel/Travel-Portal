<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StatisticsKind",
        type: "string",
        description: "The kind of the statistics record"
    )]
    enum StatisticsKind : string {
        case Fact = "fact";
        case Standings = "standings";
    }
?>