<?php
    namespace Core\Resource;

    use Core\Service\Authentication\AuthenticationService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    // TODO: Extract to a separate service.
    class IamResource extends AbstractResource {

        private const OFFLINE_ACCESS_AUTHORIZATION_CODE_FLOW_URL_FORMAT = "https://accounts.google.com/o/oauth2/v2/auth?client_id=%s&prompt=consent&redirect_uri=%s&response_type=code&access_type=offline&scope=%s";

        private readonly AuthenticationService $authenticationService;

        public function __construct(AuthenticationService $authenticationService) {
            $this->authenticationService = $authenticationService;
        }

        public static function register(App $app, AuthenticationService $authenticationService) : void {
            $resource = new self($authenticationService);

            $app->group("/iam", function($group) use($resource) {
                $group->post("/token", [$resource, "createToken"]);
                $group->get("/google/auth", [$resource, "authenticateGoogle"]);
            });
        }

        public function createToken(Request $request, Response $response, array $routeArguments) : mixed {
            $scope = $this->validateJsonBodyNullableField($request, "scope");

            $refreshToken = $this->validateJsonBodyNullableField($request, "refreshToken");
            if ($refreshToken !== null) {
                return $this->authenticationService->getIamResponseWithRefresh($refreshToken, $scope);
            }

            $username = $this->validateJsonBodyField($request, "username");
            $password = $this->validateJsonBodyField($request, "password");

            return $this->authenticationService->getIamResponseWithCredentials($username, $password, $scope);
        }

        public function authenticateGoogle(Request $request, Response $response, array $routeArguments) : mixed {
            $code = $this->validateQueryNullableParameter($request, "code");

            if ($code !== null) {
                $this->authenticationService->fetchGoogleApiRefreshToken($code);
                return $response
                    ->withHeader("Location", BASE_URL)
                    ->withStatus(302);
            }
            else {       
                return $response
                    ->withHeader("Location", sprintf(self::OFFLINE_ACCESS_AUTHORIZATION_CODE_FLOW_URL_FORMAT, GOOGLE_API_CLIENT_ID, IAM_BASE_URL . "/google/auth", implode(" ", AuthenticationService::GOOGLE_API_AUTHORIZATION_SCOPES)))
                    ->withStatus(302);
            }
        }
    }
?>