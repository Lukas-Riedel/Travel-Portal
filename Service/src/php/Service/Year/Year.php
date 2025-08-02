<?php
    namespace Service\Service\Year;

    use Service\Service\Highlight\Highlight;

    class Year implements \JsonSerializable {        
        private readonly string $id;
        private readonly ?Highlight $mainHighlight;
        private readonly array $highlights;
        private readonly array $statistics;

        public function __construct(string $id, ?Highlight $mainHighlight, array $highlights, array $statistics) {
            $this->id = $id;
            $this->highlights = $highlights;
            $this->mainHighlight = $mainHighlight;
            $this->statistics = $statistics;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getStats() : array {
            return $this->statistics;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>