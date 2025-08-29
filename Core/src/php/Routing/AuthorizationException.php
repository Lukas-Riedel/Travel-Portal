<?php
    namespace Core\Routing;

    use Core\Service\Authentication\AccessToken;

    class AuthorizationException extends \Exception {

        public function __construct(AccessToken $accessToken) {
            $userId = $accessToken->getUserId();
            parent::__construct("The user '$userId' to access this resource.", 0, null);
        }
    }
?>