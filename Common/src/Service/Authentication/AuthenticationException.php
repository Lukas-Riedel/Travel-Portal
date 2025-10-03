<?php
    namespace Common\Service\Authentication;

    class AuthenticationException extends \Exception {

        public function __construct(string $message) {
            parent::__construct($message, 0, null);
        }
    }
?>