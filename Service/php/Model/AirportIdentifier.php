<?php
    class AirportIdentifier implements JsonSerializable {        
        private $id;
        private $code;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;

        public function __construct($id, $code, $country, $latitude, $longitude, $timezone) {
            $this->id = $id;
            $this->code = $code;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
        }

        public function getId() : int {
            return $this->id;
        }

        public function setId($id) : void {
            $this->id = $id;
        }

        public function getCode() : string {
            return $this->code;
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

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>