<?php
    class PublicHoliday implements JsonSerializable {        
        private $name;
        private $country;
        private $date;

        public function __construct($name, $country, $date) {
            $this->name = $name;
            $this->country = $country;
            $this->date = $date;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCountry() : string {
            return $this->country;
        }

        public function getDate() : string {
            return $this->date;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>