<?php
    // TODO: Delete
    class EntityNotFoundException extends Exception {

        private $entity;
        private $id;

        public function __construct($entity, $id) {
            parent::__construct("The " . $entity . " with the identifier " . $id . " could not be found.", 0, null);
            $this->entity = $entity;
            $this->id = $id;
        }

        public function getEntity() {
            return $this->entity;
        }

        public function getId() {
            return $this->id;
        }
    }
?>