<?php
    namespace Common;

    class CommonConstants {
        public const ACCESS_TOKEN_ATTRIBUTE_KEY = "accessToken";
        public const USER_INFO_ATTRIBUTE_KEY = "userInfo";
        public const USER_ROLES_ATTRIBUTE_KEY = "userRoles";
        public const TRANSACTION_ID_HEADER = "Transaction-Id";
        public const REQUEST_ORIGIN_HEADER = "Request-Origin";
        public const USER_ID_HEADER = "User-Id";
        public const MANAGEMENT_RESOURCE = "/management";
        public const LIVENESS_ENDPOINT = "/liveness";
        public const READINESS_ENDPOINT = "/readiness";
    }
?>
