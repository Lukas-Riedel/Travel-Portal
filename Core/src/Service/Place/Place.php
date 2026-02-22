<?php
    namespace Core\Service\Place;

    use Core\Service\Highlight\Highlight;
    use Core\Service\Photo\Album;    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Place",
        type: "object",
        description: "A class representing a place",
        required: ["id", "name", "latitude", "longitude", "elevation", "timezone", "score"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the place",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "name",
                description: "The name of the place",
                type: "string",
                example: "Prague"
            ),
            new OA\Property(
                property: "country",
                type: "string",
                description: "The country of the place",
                example: "Czechia"
            ),
            new OA\Property(
                property: "latitude",
                type: "number",
                format: "float",
                description: "The latitude of the place",
                example: 50.0755
            ),
            new OA\Property(
                property: "longitude",
                type: "number",
                format: "float",
                description: "The longitude of the place",
                example: 14.4378
            ),
            new OA\Property(
                property: "elevation",
                type: "number",
                format: "integer",
                description: "The elevation of the place in meters",
                example: 140
            ),
            new OA\Property(
                property: "timezone",
                type: "string",
                description: "The timezone of the place",
                example: "Europe/Prague"
            ),
            new OA\Property(
                property: "mainHighlight",
                description: "The main highlight of the place",
                ref: "#/components/schemas/Highlight"
            ),
            new OA\Property(
                property: "score",
                type: "number",
                format: "float",
                description: "The score of the place",
                example: 74
            ),
            new OA\Property(
                property: "quality",
                type: "number",
                format: "float",
                description: "The quality of the place",
                example: 93
            ),
            new OA\Property(
                property: "excerpt",
                type: "string",
                description: "The excerpt of the place",
                example: "Prague, the capital of the Czech Republic, is a city where medieval charm meets vibrant modern life. Known as the \"City of a Hundred Spires,\" it dazzles with its Gothic cathedrals, baroque palaces, and the fairytale-like Prague Castle towering above the Vltava River. Strolling across the historic Charles Bridge or wandering the cobblestone lanes of the Old Town, visitors find a mix of history, art, and lively cafés. With its rich culture and timeless beauty, Prague feels both grand and intimate, a city that leaves a lasting impression."
            ),
            new OA\Property(
                property: "categories",
                description: "The categories of the place",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/CategoryIdentifier")
            ),
            new OA\Property(
                property: "highlights",
                description: "The highlights of the place",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Highlight")
            ),
            new OA\Property(
                property: "labels",
                description: "The labels of the place",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Label")
            ),
            new OA\Property(
                property: "notes",
                description: "The notes of the place",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Note")
            ),
            new OA\Property(
                property: "nearbyPlaces",
                description: "The nearby places of the place",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Place")
            ),
            new OA\Property(
                property: "dates",
                description: "The dates of the place",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Date")
            )
        ]
    )]
    class Place implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $name;
        private readonly ?string $country;
        private readonly float $latitude;
        private readonly float $longitude;
        private readonly int $elevation;
        private readonly string $timezone;
        private readonly ?Highlight $mainHighlight;
        private readonly float $score;
        private readonly ?float $quality;
        private readonly ?string $excerpt;
        private array $categories;
        private array $highlights;
        private array $labels;
        private array $notes;
        private array $nearbyPlaces;
        private array $dates;

        public function __construct(string $id, string $name, ?string $country, float $latitude, float $longitude, int $elevation,
            string $timezone, ?Highlight $mainHighlight, float $score, ?float $quality, ?string $excerpt, array $categories,
            array $highlights, array $labels, array $notes, array $nearbyPlaces, array $dates) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->elevation = $elevation;
            $this->timezone = $timezone;
            $this->score = $score;
            $this->quality = $quality;
            $this->mainHighlight = $mainHighlight;
            $this->excerpt = $excerpt;
            $this->categories = $categories;
            $this->highlights = $highlights;
            $this->labels = $labels;
            $this->notes = $notes;
            $this->nearbyPlaces = $nearbyPlaces;
            $this->dates = $dates;
        }

        public function getId() : string  {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCountry() : ?string {
            return $this->country;
        }

        public function getLatitude() : float {
            return $this->latitude;
        }

        public function getLongitude() : float {
            return $this->longitude;
        }

        public function getElevation() : int {
            return $this->elevation;
        }

        public function getTimezone() : string {
            return $this->timezone;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }
    
        public function getScore() : float {            
            return $this->score;
        }

        public function getQuality() : ?float {
            return $this->quality;
        }

        public function getExcerpt() : ?string {
            return $this->excerpt;
        }

        public function getCategories() : array {
            return $this->categories;
        }

        public function resetCategories() : void {
            $this->categories = array();
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function resetHighlights() : void {
            $this->highlights = array();
        }

        public function getLabels() : array {
            return $this->labels;
        }

        public function resetLabels() : void {
            $this->labels = array();
        }

        public function getNotes() : array {
            return $this->notes;
        }

        public function resetNotes() : void {
            $this->notes = array();
        }

        public function getNearbyPlaces() : array {
            return $this->nearbyPlaces;
        }

        public function getDates() : array {
            return $this->dates;
        }

        public function resetDates() : void {
            $this->dates = array();
        }

        public function addDate(Date $date) : void {
            $this->dates[] = $date;
        }

        public function getPlaceIdentifier() : PlaceIdentifier {
            return new PlaceIdentifier($this->id, $this->name, $this->country, $this->latitude,
                $this->longitude, $this->elevation, $this->timezone, $this->mainHighlight, $this->score, $this->quality, $this->excerpt);
        }

        public function withUpdatedDates(array $dates) : Place {
            $newPlace = clone $this;
            $newPlace->dates = $dates;
            return $newPlace;
        }

        public function findAlbum(string $albumId) : ?Album {
            foreach ($this->getDates() as &$date) {
                $album = $date->getAlbum();
                if ($album != null && $album->getId() == $albumId) {
                    return $album;
                }
            }

            return null;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>