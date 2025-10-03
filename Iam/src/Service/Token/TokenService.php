<?php
    namespace Iam\Service\Token;

    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Common\Service\Authentication\AuthenticationException;
    use Common\Service\Authentication\IamResponse;

    class TokenService {
        private const IAM_SERVICE_ACCESS_TOKEN_API_ENDPOINT_PATH = "/protocol/openid-connect/token";

        private const IAM_SERVICE_ACCESS_TOKEN_GRANT_TYPE = "client_credentials";
        private const IAM_SERVICE_REFRESH_TOKEN_GRANT_TYPE = "refresh_token";
        private const IAM_SERVICE_CREDENTIALS_GRANT_TYPE = "password";

        private readonly HttpClient $httpClient;

        public function __construct(HttpClient $httpClient) {
            $this->httpClient = $httpClient;
        }

        public function getIamResponseWithCredentials(string $username, string $password, ?string $scope) : IamResponse {
            $payload = array(
                "grant_type" => self::IAM_SERVICE_CREDENTIALS_GRANT_TYPE,
                "client_id" => IAM_APP_CLIENT_ID,
                "username" => $username,
                "password" => $password
            );

            if ($scope !== null) {
                $payload["scope"] = $scope;
            }

            return $this->getIamResponse($payload);
        }

        public function getIamResponseWithRefresh(string $refreshToken, ?string $scope) : IamResponse {
            $payload = array(
                "grant_type" => self::IAM_SERVICE_REFRESH_TOKEN_GRANT_TYPE,
                "client_id" => IAM_APP_CLIENT_ID,
                "refresh_token" => $refreshToken
            );

            if ($scope !== null) {
                $payload["scope"] = $scope;
            }

            return $this->getIamResponse($payload);
        }

        public function getIamResponseWithClientCredentials(string $clientId, string $clientSecret, ?string $scope = null) : IamResponse {
            $payload = array(
                "grant_type" => self::IAM_SERVICE_ACCESS_TOKEN_GRANT_TYPE,
                "client_id" => $clientId,
                "client_secret" => $clientSecret
            );

            if ($scope !== null) {
                $payload["scope"] = $scope;
            }

            return $this->getIamResponse($payload);
        }

        private function getIamResponse(mixed $payload) : IamResponse {
            $response = $this->httpClient->executeRequest(HttpMethod::POST, INTERNAL_IAM_BASE_URL . self::IAM_SERVICE_ACCESS_TOKEN_API_ENDPOINT_PATH, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));
                
            if (isset($response["error_description"])) {
                throw new AuthenticationException($response["error_description"]);
            }

            return new IamResponse($response["access_token"], $response["expires_in"], $response["refresh_token"] ?? null, $response["refresh_expires_in"] ?? null);
        }
    }
?>