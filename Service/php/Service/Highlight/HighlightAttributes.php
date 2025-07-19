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
            return $this->composition !== NULL && $this->sky !== NULL && $this->shadows !== NULL && $this->circumstances !== NULL
                ? ($this->composition + $this->sky + $this->shadows + $this->circumstances) / 4.0
                : NULL;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>