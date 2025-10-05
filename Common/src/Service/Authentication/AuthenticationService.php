<?php
    namespace Common\Service\Authentication;

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class AuthenticationService {
        
        public function authenticate(string $accessToken) : UserInfo {
            try {
                // TODO: Call (and cache) /protocol/openid-connect/certs  to get JWKS_PUBLIC_KEY.
                $decoded = JWT::decode($accessToken, new Key(JWKS_PUBLIC_KEY, "RS256"));
                return new UserInfo($decoded->sub, $decoded->azp, isset($decoded->resource_access->{IAM_APP_CLIENT_ID}->roles) 
                    ? $decoded->resource_access->{IAM_APP_CLIENT_ID}->roles : array());
            }
            catch (\Throwable $e) {
                throw new AuthenticationException("An error occurred when decoding JWT token. " . $e->getMessage() . ".");
            }
        }
    }
?>