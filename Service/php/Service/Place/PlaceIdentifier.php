<?php
    namespace Service\Service\Place;

    use Service\Service\Geocoding\Location;
    use Service\Service\Highlight\Highlight;

    class PlaceIdentifier implements \JsonSerializable {        
        private ?string $id;
        private readonly string $name;
        private readonly string $country;
        private readonly float $latitude;
        private readonly float $longitude;
        private readonly string $timezone;
        private readonly ?Highlight $mainHighlight;
        private readonly ?string $excerpt;

        public function __construct(?string $id, string $name, string $country, float $latitude,
            float $longitude, string $timezone, ?Highlight $mainHighlight, ?string $excerpt) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
            $this->mainHighlight = $mainHighlight;
            $this->excerpt = $excerpt;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
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

        public function getExcerpt() : ?string {
            return $this->excerpt;
        }

        public function getLocation() : Location {
            return new Location($this->country, $this->latitude, $this->longitude, $this->timezone);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>