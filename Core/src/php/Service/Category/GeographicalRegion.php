<?php
    namespace Core\Service\Category;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "GeographicalRegion",
        type: "object",
        description: "An object representing a geographical region",
        required: ["categoryId", "radius", "geoJson"],
        properties: [
            new OA\Property(
                property: "categoryId",
                type: "string",
                description: "The identifier of the category representing the geographical region",
                example: "bee9f1e9-8f28-40ed-b09e-9dfad1d4f199"
            ),
            new OA\Property(
                property: "countryCategoryId",
                type: "string",
                description: "The identifier of the country category related to the region",
                example: "6e5b634c-d091-49ef-a418-6b925331f41d"
            ),
            new OA\Property(
                property: "radius",
                type: "integer",
                description: "The radius of the geographical region in kilometers",
                example: 5
            ),
            new OA\Property(
                property: "geoJson",
                description: "The GeoJSON object defining the shape of the geographical region",
                type: "object",
                example: '{"type":"Polygon","coordinates":[[[14.4,50.0],[14.5,50.0],[14.5,50.1],[14.4,50.1],[14.4,50.0]]]}'
            )
        ]
    )]    
    // TODO: Replace strings with CategoryIdentifiers.
    class GeographicalRegion implements \JsonSerializable {        
        private readonly string $categoryId;
        private readonly ?string $countryCategoryId;
        private readonly int $radius;
        private readonly mixed $geoJson;

        public function __construct(string $categoryId, ?string $countryCategoryId, int $radius, mixed $geoJson) {
            $this->categoryId = $categoryId;
            $this->countryCategoryId = $countryCategoryId;
            $this->radius = $radius;
            $this->geoJson = $geoJson;
        }

        public function getCategoryId() : string {
            return $this->categoryId;
        }

        public function getCountryCategoryId() : ?string {
            return $this->countryCategoryId;
        }

        public function getRadius() : int {
            return $this->radius;
        }

        public function getGeoJson() : mixed {
            return $this->geoJson;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>