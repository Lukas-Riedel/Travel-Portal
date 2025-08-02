<?php
    namespace Service\Routing;

    class NotFoundException extends \Exception {

        public function __construct(string $key) {
            parent::__construct("The entity with the key '$key' could not be found.", 0, NULL);
        }
    }
?>