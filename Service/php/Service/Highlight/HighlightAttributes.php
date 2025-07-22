<?php
    namespace Service\Service\Highlight;

    class HighlightAttributes implements \JsonSerializable {
        private readonly ?int $composition;
        private readonly ?int $sky;
        private readonly ?int $shadows;
        private readonly ?int $circumstances;

        public function __construct(?int $composition, ?int $sky, ?int $shadows, ?int $circumstances) {
            $this->composition = $composition;
            $this->sky = $sky;
            $this->shadows = $shadows;
            $this->circumstances = $circumstances;
        }

        public function getComposition() : ?int {
            return $this->composition;
        }

        public function getSky() : ?int {
            return $this->sky;
        }

        public function getShadows() : ?int {
            return $this->shadows;
        }

        public function getQuality() : ?float {
            if ($this->composition === NULL || $this->sky === NULL || $this->shadows === NULL || $this->circumstances === NULL) {
                return NULL;
            }

            return 4.0 / (1 / $this->composition + 1 / $this->sky + 1 / $this->shadows + 1 / $this->circumstances);
        }


        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>