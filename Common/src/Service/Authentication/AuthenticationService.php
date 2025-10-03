<?php
    namespace Common\Service\Authentication;

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class AuthenticationService {
        
        public function authenticate(string $accessToken) : UserInfo {
            $decoded = JWT::decode($accessToken, new Key(JWKS_PUBLIC_KEY, "RS256"));
            return new UserInfo($decoded->sub, $decoded->resource_access->{IAM_APP_CLIENT_ID}->roles, 0);
        }
    }
?>