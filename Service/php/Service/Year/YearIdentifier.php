<?php
    namespace Service\Service\Year;

    use Service\Service\Highlight\Highlight;

    class YearIdentifier implements \JsonSerializable {        
        private ?string $id;
        private readonly ?Highlight $mainHighlight;

        public function __construct(?string $id, ?Highlight $mainHighlight) {
            $this->id = $id;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
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