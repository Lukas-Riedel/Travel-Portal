<?php
    namespace Common\Service\Authentication;

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class AuthenticationService {

        private readonly string $iamAppClientId;
        private readonly string $jwksPublicKey;

        public function __construct(string $iamAppClientId, string $jwksPublicKey) {
            $this->iamAppClientId = $iamAppClientId;
            $this->jwksPublicKey = $jwksPublicKey;
        }
        
        public function authenticate(string $accessToken) : UserInfo {
            try {
                // TODO: Call (and cache) /protocol/openid-connect/certs  to get JWKS public key.
                $decoded = JWT::decode($accessToken, new Key($this->jwksPublicKey, "RS256"));
                return new UserInfo($decoded->sub, $decoded->azp, isset($decoded->resource_access->{$this->iamAppClientId}->roles) 
                    ? $decoded->resource_access->{$this->iamAppClientId}->roles : array());
            }
            catch (\Throwable $e) {
                throw new AuthenticationException("An error occurred when decoding JWT token '" . $accessToken . "'. " . $e->getMessage() . ".");
            }
        }
    }
?>