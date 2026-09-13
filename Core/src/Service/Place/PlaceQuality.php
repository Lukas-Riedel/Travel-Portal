<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PlaceQuality",
        description: "A class representing the quality of the place",
        properties: [
            new OA\Property(
                property: "rating",
                type: "number",
                format: "float",
                description: "The quality rating of the place",
                example: 88.5
            ),
            new OA\Property(
                property: "tier",
                ref: "#/components/schemas/PlaceQualityTier",
                description: "The quality tier of the place"
            )
        ]
    )]
    class PlaceQuality implements \JsonSerializable {

        private readonly ?float $rating;
        private readonly ?PlaceQualityTier $tier;

        public function __construct(?float $rating, ?PlaceQualityTier $tier) {
            $this->rating = $rating;
            $this->tier = $tier;
        }

        public function getRating() : ?float {
            return $this->rating;
        }

        public function getTier() : ?PlaceQualityTier {
            return $this->tier;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
