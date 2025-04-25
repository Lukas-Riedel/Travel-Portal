<?php
    namespace Service\Service\Stay;

    class Stay implements \JsonSerializable {        
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

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>