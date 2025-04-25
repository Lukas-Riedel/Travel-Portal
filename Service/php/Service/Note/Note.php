<?php
    namespace Service\Service\Note;

    class Note implements \JsonSerializable {        
        private ?string $id;
        private readonly string $content;

        public function __construct(?string $id, string $content) {
            $this->id = $id;
            $this->content = $content;
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

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>