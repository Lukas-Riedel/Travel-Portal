<?php
    namespace Service\Service\Category;
    
    class CategoryMetadata implements \JsonSerializable {        
        private readonly ?string $color;
        private readonly ?string $unicode;
        private readonly ?string $publicHolidaysCalendar;

        public function __construct(?string $color, ?string $unicode, ?string $publicHolidaysCalendar) {
            $this->color = $color;
            $this->unicode = $unicode;
            $this->publicHolidaysCalendar = $publicHolidaysCalendar;
        }

        public function getColor() : ?string {
            return $this->color;
        }

        public function getUnicode() : ?string {
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