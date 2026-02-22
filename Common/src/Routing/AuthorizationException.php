<?php
    namespace Common\Routing;

    use Common\Service\Authentication\UserInfo;

    class AuthorizationException extends \Exception {

        public function __construct(UserInfo $userInfo) {
            $userId = $userInfo->getUserId();
            parent::__construct("The user '$userId' is not authorized to access this resource.", 0, null);
        }
    }
?>