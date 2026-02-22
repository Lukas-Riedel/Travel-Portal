<?php
    namespace Iam\Resource;

    use Common\Resource\AbstractResource;
    use Iam\Service\Token\TokenService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class TokenResource extends AbstractResource {

        private readonly TokenService $tokenService;

        public function __construct(TokenService $tokenService) {
            $this->tokenService = $tokenService;
        }

        public static function register(App $app, TokenService $tokenService) : void {
            $resource = new self($tokenService);

            $app->group("/token", function($group) use($resource) {
                $group->post("", [$resource, "createToken"]);
            });
        }

        public function createToken(Request $request, Response $response, array $routeArguments) : mixed {
            $scope = $this->getJsonBodyField($request, "scope");
            
            $clientId = $this->getJsonBodyField($request, "clientId");            
            $clientSecret = $this->getJsonBodyField($request, "clientSecret");
            if ($clientId !== null && $clientSecret !== null) {
                return $this->tokenService->getIamResponseWithClientCredentials($clientId, $clientSecret, $scope);
            }

            $refreshToken = $this->getJsonBodyField($request, "refreshToken");
            if ($refreshToken !== null) {
                return $this->tokenService->getIamResponseWithRefresh($refreshToken, $scope);
            }

            $username = $this->requireJsonBodyField($request, "username");
            $password = $this->requireJsonBodyField($request, "password");

            return $this->tokenService->getIamResponseWithCredentials($username, $password, $scope);
        }
    }
?>