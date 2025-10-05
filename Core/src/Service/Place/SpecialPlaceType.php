<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "SpecialPlaceType",
        type: "string",
        description: "The type of the special place"
    )]
    enum SpecialPlaceType : string {
        case Candidate = "candidate";
        case Permanent = "permanent";

        public function getTableName() : string {
            return match ($this) {
                self::Candidate => "place_candidate",
                self::Permanent => "place_permanent"
            };
        }
    }
?>