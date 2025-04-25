<?php
    class CategoryMetadata implements JsonSerializable {        
        private $color;
        private $unicode;
        private $publicHolidaysCalendar;

        public function __construct($color, $unicode, $publicHolidaysCalendar) {
            $this->color = $color;
            $this->unicode = $unicode;
            $this->publicHolidaysCalendar = $publicHolidaysCalendar;
        }

        public function getColor() : string {
            return $this->color;
        }

        public function getUnicode() : string {
            return $this->unicode;
        }

        public function getPublicHolidaysCalendar() : ?string {
            return $this->publicHolidaysCalendar;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>