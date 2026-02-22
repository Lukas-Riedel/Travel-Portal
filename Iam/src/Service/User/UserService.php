<?php
    namespace Iam\Service\User;

    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Common\Service\Authentication\UserRole;
    use Iam\Service\Token\TokenService;

    class UserService {

        private const CLIENT_API_ENDPOINT_PATH_FORMAT = "/clients?clientId=%s";
        private const USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT = "/clients/%s/roles/%s/users";

        private readonly TokenService $tokenService;

        private readonly HttpClient $httpClient;

        private readonly string $iamAppClientId;
        private readonly string $iamBackendClientId;
        private readonly string $iamBackendClientSecret;
        private readonly string $internalAdminIamBaseUrl;

        public function __construct(TokenService $tokenService, HttpClient $httpClient, string $iamAppClientId, string $iamBackendClientId, string $iamBackendClientSecret, string $internalAdminIamBaseUrl) {
            $this->tokenService = $tokenService;
            $this->httpClient = $httpClient;
            $this->iamAppClientId = $iamAppClientId;
            $this->iamBackendClientId = $iamBackendClientId;
            $this->iamBackendClientSecret = $iamBackendClientSecret;
            $this->internalAdminIamBaseUrl = $internalAdminIamBaseUrl;
        }

        public function getUserIdsWithRole(UserRole $role) : array {
            $accessToken = $this->tokenService->getIamResponseWithClientCredentials($this->iamBackendClientId, $this->iamBackendClientSecret)->getAccessToken();

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->internalAdminIamBaseUrl . sprintf(self::CLIENT_API_ENDPOINT_PATH_FORMAT, $this->iamAppClientId),
                array("Authorization: Bearer " . $accessToken));

            if (!is_array($response) || count($response) !== 1 || !isset($response[0]["id"])) {
                throw new \RuntimeException("There must be exactly one client with the specified identifier. Response: " . json_encode($response));
            }

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->internalAdminIamBaseUrl . sprintf(self::USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT, $response[0]["id"], $role->value),
                array("Authorization: Bearer " . $accessToken));
                
            if (!is_array($response)) {
                throw new \RuntimeException("The response with users is not an array. Response: " . json_encode($response));
            }

            return array_map(fn($user) => $user["id"], $response);
        }
    }
?>