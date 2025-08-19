<?php
    namespace Core\Client;

    class CalendarEvent implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $summary;
        private readonly ?string $location;
        private readonly int $start;
        private readonly int $end;
        private readonly array $attributes;

        public function __construct(string $id, string $summary, ?string $location, int $start, int $end, array $attributes) {
            $this->id = $id;
            $this->summary = $summary;
            $this->location = $location;
            $this->start = $start;
            $this->end = $end;
            $this->attributes = $attributes;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getSummary() : string {
            return $this->summary;
        }

        public function getLocation() : ?string {
            return $this->location;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function getAttributes() : array {
            return $this->attributes;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>