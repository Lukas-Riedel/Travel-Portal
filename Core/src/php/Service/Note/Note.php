<?php
    namespace Core\Service\Note;

    class Note implements \JsonSerializable {        
        private ?string $id;
        private readonly string $content;
        private readonly int $timestamp;

        public function __construct(?string $id, string $content, int $timestamp) {
            $this->id = $id;
            $this->content = $content;
            $this->timestamp = $timestamp;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getContent() : string {
            return $this->content;
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