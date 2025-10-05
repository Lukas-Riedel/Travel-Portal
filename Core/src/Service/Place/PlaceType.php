<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "PlaceType",
        type: "string",
        description: "The type of the place"
    )]
    enum PlaceType : string {
        case Regular = "regular";
        case Candidate = "candidate";
    }
?>