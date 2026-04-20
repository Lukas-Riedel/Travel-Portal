<?php
    namespace Core\Service\Category;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "GeographicalRegion",
        type: "object",
        description: "An object representing a geographical region",
        required: ["category", "radius", "geoJson"],
        properties: [
            new OA\Property(
                property: "category",
                description: "The category representing the geographical region",
                ref: "#/components/schemas/CategoryIdentifier"
            ),
            new OA\Property(
                property: "countryCategory",
                description: "The country category related to the region",
                ref: "#/components/schemas/CategoryIdentifier"
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
    class GeographicalRegion implements \JsonSerializable {  
              
        private readonly CategoryIdentifier $category;
        private readonly ?CategoryIdentifier $countryCategory;
        private readonly int $radius;
        private readonly mixed $geoJson;

        public function __construct(CategoryIdentifier $category, ?CategoryIdentifier $countryCategory, int $radius, mixed $geoJson) {
            $this->category = $category;
            $this->countryCategory = $countryCategory;
            $this->radius = $radius;
            $this->geoJson = $geoJson;
        }

        public function getCategory() : CategoryIdentifier {
            return $this->category;
        }

        public function getCountryCategory() : ?CategoryIdentifier {
            return $this->countryCategory;
        }

        public function getRadius() : int {
            return $this->radius;
        }

        public function getGeoJson() : mixed {
            return $this->geoJson;
        }

        public function isEmpty() : bool {
            return $this->geoJson == null || !isset($this->geoJson["geometry"]);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>