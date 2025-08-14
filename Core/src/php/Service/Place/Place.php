<?php
    namespace Core\Service\Place;

    use Core\Service\Highlight\Highlight;
    use Core\Service\Photo\Album;

    class Place implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $name;
        private readonly string $country;
        private readonly float $latitude;
        private readonly float $longitude;
        private readonly string $timezone;
        private readonly ?Highlight $mainHighlight;
        private readonly float $score;
        private readonly ?float $quality;
        private readonly ?string $excerpt;
        private readonly array $categories;
        private readonly array $highlights;
        private readonly array $labels;
        private readonly array $notes;
        private array $dates;

        public function __construct(string $id, string $name, string $country, float $latitude, float $longitude,
            string $timezone, ?Highlight $mainHighlight, float $score, ?float $quality, ?string $excerpt, array $categories,
            array $highlights, array $labels, array $notes, array $dates) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
            $this->score = $score;
            $this->quality = $quality;
            $this->mainHighlight = $mainHighlight;
            $this->excerpt = $excerpt;
            $this->categories = $categories;
            $this->highlights = $highlights;
            $this->labels = $labels;
            $this->notes = $notes;
            $this->dates = $dates;
        }

        public function getId() : string  {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCountry() : string {
            return $this->country;
        }

        public function getLatitude() : float {
            return $this->latitude;
        }

        public function getLongitude() : float {
            return $this->longitude;
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

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getLabels() : array {
            return $this->labels;
        }

        public function getNotes() : array {
            return $this->notes;
        }

        public function getDates() : array {
            return $this->dates;
        }

        public function addDate(Date $date) : void {
            $this->dates[] = $date;
        }

        public function getPlaceIdentifier() : PlaceIdentifier {
            return new PlaceIdentifier($this->id, $this->name, $this->country, $this->latitude,
                $this->longitude, $this->timezone, $this->mainHighlight, $this->score, $this->quality, $this->excerpt);
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