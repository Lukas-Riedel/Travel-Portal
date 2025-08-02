<?php
    namespace Service\Service\Monitoring;

    class DataConsistencyIssue implements \JsonSerializable {
        private readonly string $name;
        private readonly mixed $context;
        private readonly int $timestamp;

        public function __construct(string $name, mixed $context, int $timestamp) {
            $this->name = $name;
            $this->context = $context;
            $this->timestamp = $timestamp;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getContext() : mixed {
            return $this->context;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>