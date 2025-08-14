<?php
    namespace Core\Routing;

    use Core\Service\Authentication\AccessToken;

    class AuthorizationException extends \Exception {

        public function __construct(AccessToken $accessToken) {
            // TODO: Improve the message based on the data from the access token.
            parent::__construct("You are not allowed to access this resource.", 0, NULL);
        }
    }
?>