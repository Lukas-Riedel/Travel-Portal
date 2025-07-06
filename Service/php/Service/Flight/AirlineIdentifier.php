<?php
    namespace Service\Service\Flight;

    class AirlineIdentifier implements \JsonSerializable {        
        private readonly string $code;
        private readonly string $name;
        private readonly ?string $logo;

        public function __construct(string $code, string $name, ?string $logo) {
            $this->code = $code;
            $this->name = $name;
            $this->logo = $logo;
        }

        public function getCode() : string {
            return $this->code;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getLogo() : ?string {
            return $this->logo;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>