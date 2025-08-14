<?php
    namespace Core\Service\TimeTracking;

    class TimeTrackingEvent implements \JsonSerializable {        
        private ?string $id;
        private readonly string $description;
        private readonly float $hours;
        private readonly int $timestamp;
        private readonly TimeTrackingEventType $type;
        private readonly float $balance;

        public function __construct(?string $id, string $description, float $hours,
            int $timestamp, TimeTrackingEventType $type, float $balance) {
            $this->id = $id;
            $this->description = $description;
            $this->hours = $hours;
            $this->timestamp = $timestamp;
            $this->type = $type;
            $this->balance = $balance;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getDescription() : string {
            return $this->description;
        }

        public function getHours() : float {
            return $this->hours;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }
        
        public function getType() : TimeTrackingEventType {
            return $this->type;
        }

        public function getBalance() : float {
            return $this->balance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>