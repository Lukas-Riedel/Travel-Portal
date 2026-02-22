<?php
    namespace Core\Service\Trip;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TripType",
        type: "string",
        description: "The type of the trip"
    )]
    enum TripType : string {
        case Regular = "regular";
        case Candidate = "candidate";
    }
?>