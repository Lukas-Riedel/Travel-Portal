<?php
    namespace Service\Service\Flight;

    class Airline implements \JsonSerializable {        
        private ?string $id;
        private readonly string $name;
        private readonly array $codes;
        private readonly ?string $logo;

        public function __construct(?string $id, string $name, array $codes, ?string $logo) {
            $this->id = $id;
            $this->name = $name;
            $this->codes = $codes;
            $this->logo = $logo;
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

        public function getCodes() : array {
            return $this->codes;
        }

        public function getLogo() : ?string {
            return $this->logo;
        }

        public function getAirlineIdentifier() : AirlineIdentifier {
            return new AirlineIdentifier($this->id, $this->name);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>