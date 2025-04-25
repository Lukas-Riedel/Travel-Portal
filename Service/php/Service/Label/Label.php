<?php
    namespace Service\Service\Label;

    class Label implements \JsonSerializable {     

        private ?string $id;
        private readonly string $name;

        public function __construct(?string $id, string $name) {
            $this->id = $id;
            $this->name = $name;
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

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>