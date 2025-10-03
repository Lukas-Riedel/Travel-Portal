<?php
    namespace Iam\Service\Google;

    use Google\Auth\Credentials\ServiceAccountCredentials;
    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Common\Service\Authentication\IamResponse;
    use Iam\Client\Encryption\EncryptionClient;
    use Iam\Service\User\UserService;

    class GoogleService {
        
        public const GOOGLE_AUTH_CALLBACK_API_ENDPOINT_PATH = "/google/auth/callback";

        private const REQUIRED_AUTHENTICATION_ROLE = "ADMIN";

        private const GOOGLE_API_IAM_TOKEN_URL = "https://oauth2.googleapis.com/token";
        
        private const GOOGLE_FCM_ACCOUNT_TYPE = "service_account";
        private const GOOGLE_FCM_AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
        private const GOOGLE_FCM_AUTH_PROVIDER_X509_CERTIFICATE_URL = "https://www.googleapis.com/oauth2/v1/certs";
        private const GOOGLE_FCM_UNIVERSE_DOMAIN = "googleapis.com";

        private const GOOGLE_API_REFRESH_TOKEN_GRANT_TYPE = "refresh_token";
        private const GOOGLE_API_AUTHORIZATION_CODE_GRANT_TYPE = "authorization_code";
        private const GOOGLE_API_ACCESS_TYPE = "offline";
        
        private const GOOGLE_REFRESH_TOKEN_FILE_PATH = __DIR__ . "/../../../google.txt";

        public const GOOGLE_API_AUTHORIZATION_SCOPES = array(
            "https://www.googleapis.com/auth/photoslibrary.appendonly",
            "https://www.googleapis.com/auth/photoslibrary.readonly.appcreateddata",
            "https://www.googleapis.com/auth/photoslibrary.edit.appcreateddata",
            "https://www.googleapis.com/auth/fitness.activity.read",
            "https://www.googleapis.com/auth/fitness.location.read",
            "https://www.googleapis.com/auth/calendar",
            "https://www.googleapis.com/auth/drive"
        );
        
        private const GOOGLE_FCM_AUTHORIZATION_SCOPES = array(
            "https://www.googleapis.com/auth/firebase.messaging",
            "https://www.googleapis.com/auth/cloud-platform",
        );

        private readonly UserService $userService;

        private readonly HttpClient $httpClient;
        private readonly EncryptionClient $encryptionClient;

        public function __construct(UserService $userService, HttpClient $httpClient, EncryptionClient $encryptionClient) {
            $this->userService = $userService;
            $this->httpClient = $httpClient;
            $this->encryptionClient = $encryptionClient;
        }

        public function getGoogleApiAccessToken() : IamResponse {
            if (!file_exists($this::GOOGLE_REFRESH_TOKEN_FILE_PATH)) {
                throw new \RuntimeException("The refresh token has not been set yet.");
            }
            
            $refreshToken = $this->encryptionClient->decrypt(trim(file_get_contents($this::GOOGLE_REFRESH_TOKEN_FILE_PATH)));

            $payload = array(
                "grant_type" => self::GOOGLE_API_REFRESH_TOKEN_GRANT_TYPE,
                "client_id" => GOOGLE_API_CLIENT_ID,
                "client_secret" => GOOGLE_API_CLIENT_SECRET,
                "redirect_uri" => BASE_URL,
                "refresh_token" => $refreshToken,
                "access_type" => self::GOOGLE_API_ACCESS_TYPE
            );     

            $response = $this->httpClient->executeRequest(HttpMethod::POST, self::GOOGLE_API_IAM_TOKEN_URL, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            return new IamResponse($response["access_token"], $response["expires_in"], null, null);
        }

        public function getGoogleFcmAccessToken() : IamResponse {
            $credentials = array(
                "type" => self::GOOGLE_FCM_ACCOUNT_TYPE,
                "project_id" => FCM_PROJECT_ID,
                "private_key_id" => FCM_PRIVATE_KEY_ID,
                "private_key" => FCM_PRIVATE_KEY,
                "client_email" => FCM_CLIENT_EMAIL,
                "client_id" => FCM_CLIENT_ID,
                "auth_uri" => self::GOOGLE_FCM_AUTH_URL,
                "token_uri" => self::GOOGLE_API_IAM_TOKEN_URL,
                "auth_provider_x509_cert_url" => self::GOOGLE_FCM_AUTH_PROVIDER_X509_CERTIFICATE_URL,
                "client_x509_cert_url" => FCM_CLIENT_X509_CERTIFICATE_URL,
                "universe_domain" => self::GOOGLE_FCM_UNIVERSE_DOMAIN,
            );
            
            $response = (new ServiceAccountCredentials(self::GOOGLE_FCM_AUTHORIZATION_SCOPES, $credentials))->fetchAuthToken();

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            return new IamResponse($response["access_token"], $response["expires_in"], null, null);
        }

        public function fetchGoogleApiRefreshToken(string $code, string $userId) : void {
            $payload = array(
                "code" => $code,
                "client_id" => GOOGLE_API_CLIENT_ID,
                "client_secret" => GOOGLE_API_CLIENT_SECRET,
                "redirect_uri" => IAM_BASE_URL . self::GOOGLE_AUTH_CALLBACK_API_ENDPOINT_PATH,
                "grant_type" => self::GOOGLE_API_AUTHORIZATION_CODE_GRANT_TYPE,
                "access_type" => self::GOOGLE_API_ACCESS_TYPE
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, self::GOOGLE_API_IAM_TOKEN_URL,
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["refresh_token"])) {
                throw new \RuntimeException("The refresh token could not be obtained. Response: " . json_encode($response));
            }
                
            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            if (!in_array($userId, $this->userService->getUserIdsWithRole(self::REQUIRED_AUTHENTICATION_ROLE))) {
                throw new \RuntimeException("The user '$userId' is not authorized to perform the authentication.");
            }

            file_put_contents($this::GOOGLE_REFRESH_TOKEN_FILE_PATH, $this->encryptionClient->encrypt($response["refresh_token"]));
        }
    }
?>