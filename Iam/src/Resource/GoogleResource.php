<?php
    namespace Iam\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\AuthenticationService;
    use Iam\Client\Encryption\EncryptionClient;
    use Iam\Service\Google\GoogleService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class GoogleResource extends AbstractResource {

        private const OFFLINE_ACCESS_AUTHORIZATION_CODE_FLOW_URL_FORMAT = "https://accounts.google.com/o/oauth2/v2/auth?client_id=%s&prompt=consent&redirect_uri=%s&response_type=code&access_type=offline&state=%s&scope=%s";

        private readonly AuthenticationService $authenticationService;
        private readonly GoogleService $googleService;
        
        private readonly EncryptionClient $encryptionClient;

        private readonly string $googleApiClientId;
        private readonly string $iamBaseUrl;

        public function __construct(AuthenticationService $authenticationService, GoogleService $googleService, EncryptionClient $encryptionClient, string $googleApiClientId, string $iamBaseUrl) {
            $this->authenticationService = $authenticationService;
            $this->googleService = $googleService;
            $this->encryptionClient = $encryptionClient;
            $this->googleApiClientId = $googleApiClientId;
            $this->iamBaseUrl = $iamBaseUrl;
        }

        public static function register(App $app, AuthenticationService $authenticationService, GoogleService $googleService, EncryptionClient $encryptionClient, string $googleApiClientId, string $iamBaseUrl) : void {
            $resource = new self($authenticationService, $googleService, $encryptionClient, $googleApiClientId, $iamBaseUrl);

            $app->group("/google", function($group) use($resource) {
                $group->post("/token/api", [$resource, "createApiToken"]);
                $group->post("/token/fcm", [$resource, "createFcmToken"]);
                $group->post("/auth", [$resource, "initAuthentication"]);
                $group->get("/auth/callback", [$resource, "handleAuthenticationCallback"]);
            });
        }

        public function createApiToken(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireServiceAccount($request);

            return $this->googleService->getGoogleApiAccessToken();
        }
        
        public function createFcmToken(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireServiceAccount($request);

            return $this->googleService->getGoogleFcmAccessToken();
        }
        
        public function handleAuthenticationCallback(Request $request, Response $response, array $routeArguments) : mixed {
            $code = $this->requireQueryParameter($request, "code");
            
            $userId = $this->encryptionClient->decrypt($this->requireQueryParameter($request, "state"));

            $this->googleService->fetchGoogleApiRefreshToken($code, $userId);

            return $response
                ->withHeader("Location", $this->iamBaseUrl)
                ->withStatus(302);
        }

        public function initAuthentication(Request $request, Response $response, array $routeArguments) : mixed {
            $token = $this->requireJsonBodyField($request, "token");

            return $response
                ->withHeader("Location", sprintf(self::OFFLINE_ACCESS_AUTHORIZATION_CODE_FLOW_URL_FORMAT, $this->googleApiClientId, 
                    $this->iamBaseUrl . GoogleService::GOOGLE_AUTH_CALLBACK_API_ENDPOINT_PATH, 
                    rawurlencode($this->encryptionClient->encrypt($this->authenticationService->authenticate($token)->getUserId())),
                    rawurlencode(implode(" ", GoogleService::GOOGLE_API_AUTHORIZATION_SCOPES))))
                ->withStatus(302);
        }
    }
?>