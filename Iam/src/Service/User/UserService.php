<?php
    namespace Iam\Service\User;

    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Iam\Service\Token\TokenService;

    class UserService {

        private const CLIENT_API_ENDPOINT_PATH_FORMAT = "/clients?clientId=%s";
        private const USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT = "/clients/%s/roles/%s/users";

        private readonly TokenService $tokenService;

        private readonly HttpClient $httpClient;

        public function __construct(TokenService $tokenService, HttpClient $httpClient) {
            $this->tokenService = $tokenService;
            $this->httpClient = $httpClient;
        }

        public function getUserIdsWithRole(string $role) : array {
            $accessToken = $this->tokenService->getIamResponseWithClientCredentials(IAM_BACKEND_CLIENT_ID, IAM_BACKEND_CLIENT_SECRET)->getAccessToken();

            $response = $this->httpClient->executeRequest(HttpMethod::GET, IAM_ADMIN_BASE_URL . sprintf(self::CLIENT_API_ENDPOINT_PATH_FORMAT, IAM_APP_CLIENT_ID),
                array("Authorization: Bearer " . $accessToken));

            if (!is_array($response) || count($response) !== 1 || !isset($response[0]["id"])) {
                throw new \RuntimeException("There must be exactly one client with the specified identifier. Response: " . json_encode($response));
            }

            $response = $this->httpClient->executeRequest(HttpMethod::GET, IAM_ADMIN_BASE_URL . sprintf(self::USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT, $response[0]["id"], $role),
                array("Authorization: Bearer " . $accessToken));
                
            if (!is_array($response)) {
                throw new \RuntimeException("The response with users is not an array. Response: " . json_encode($response));
            }

            return array_map(fn($user) => $user["id"], $response);
        }
    }
?>