<?php
    namespace Core\Routing;

    use Core\Service\Authentication\UserInfo;

    class AuthorizationException extends \Exception {

        public function __construct(UserInfo $userInfo) {
            $userId = $userInfo->getUserId();
            parent::__construct("The user '$userId' is unathorized to access this resource.", 0, null);
        }
    }
?>