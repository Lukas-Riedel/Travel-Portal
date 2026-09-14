<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PlaceQualityTier",
        type: "string",
        description: "An enum representing a place quality tier",
        enum: ["S", "A", "B", "C", "D", "E"]
    )]
    enum PlaceQualityTier : int {
        case S = 0;
        case A = 1;
        case B = 2;
        case C = 3;
        case D = 4;
        case E = 5;

    }
?>
