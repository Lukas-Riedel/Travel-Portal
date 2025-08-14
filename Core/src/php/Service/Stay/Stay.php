<?php
    namespace Core\Service\Stay;

    class Stay implements \JsonSerializable {
        private const ONE_DAY_SECONDS = 86400;

        private readonly string $name;
        private readonly string $address;
        private readonly int $start;
        private readonly int $end;

        public function __construct(string $name, string $address, int $start, int $end) {
            $this->name = $name;
            $this->address = $address;
            $this->start = $start;
            $this->end = $end;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getAddress() : string {
            return $this->address;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function getNightsCount() : int {
            return round(($this->end - $this->start) / self::ONE_DAY_SECONDS) - 1;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>