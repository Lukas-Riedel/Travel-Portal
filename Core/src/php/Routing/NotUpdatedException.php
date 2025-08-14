<?php
    namespace Core\Routing;

    class NotUpdatedException extends \Exception {

        public function __construct(string $key) {
            parent::__construct("The entity with the key '$key' could not be updated.", 0, null);
        }
    }
?>