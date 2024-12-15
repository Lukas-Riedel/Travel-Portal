<?php
    class Note implements JsonSerializable {        
        private $id;
        private $content;

        public function __construct($id, $content) {
            $this->id = $id;
            $this->content = $content;
        }

        public function getId() : int {
            return $this->id;
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