<?php
    namespace Service\Service\Trip;
    
    use Service\Service\Highlight\Highlight;

    class TripIdentifier implements \JsonSerializable {        
        private ?string $id;
        private readonly string $name;
        private readonly int $year;
        private readonly ?Highlight $mainHighlight;

        public function __construct(?string $id, string $name, int $year, ?Highlight $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->mainHighlight = $mainHighlight;
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

        public function getYear() : ?int {
            return $this->year;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>