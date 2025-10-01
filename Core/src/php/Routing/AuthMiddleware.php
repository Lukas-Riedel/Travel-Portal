<?php
    namespace Core\Routing;

    use Core\Common\CommonConstants;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Core\Service\Authentication\AccessToken;
    use Core\Service\Authentication\AuthenticationException;
    use Core\Service\Authentication\AuthenticationService;

    class AuthMiddleware implements MiddlewareInterface {

        private const BEARER_TOKEN_PATTERN = "/Bearer\s+(.*)$/i";
        private const AUTHORIZATION_HEADER = "Authorization";

        private const WHITELISTED_PATHS = array("/swagger", "/events/webhook", "/iam");

        private readonly AuthenticationService $authenticationService;
        private readonly string $basePath;

        public function __construct(AuthenticationService $authenticationService, string $basePath) {
            $this->authenticationService = $authenticationService;
            $this->basePath = $basePath;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            if ($request->getUri()->getPath() === ($this->basePath . "/") 
                || count(array_filter(self::WHITELISTED_PATHS, fn($path) => str_starts_with($request->getUri()->getPath(), $this->basePath . $path))) > 0) {
                return $handler->handle($request);
            }

            $accessToken = $this->tryExtractAccessToken($request, self::AUTHORIZATION_HEADER);
            if ($accessToken === null) {
                throw new AuthenticationException("The access token was not provided.");
            }

            return $handler->handle($request->withAttribute(CommonConstants::ACCESS_TOKEN_REQUEST_ATTRIBUTE_KEY, $accessToken));
        }

        private function tryExtractAccessToken(ServerRequestInterface $request, string $header) : ?AccessToken {
            $authHeader = $request->getHeaderLine($header);
            if (preg_match(self::BEARER_TOKEN_PATTERN, $authHeader, $matches)) {
                try {                    
                    return $this->authenticationService->authenticate($matches[1]);
                }
                catch (\Throwable $e) {
                    // TODO: After removing this code, make sure what exceptions can be thrown by JWT validator, and rethrow accordingly.
                    return $this->authenticationService->getAccessToken($matches[1]);
                }
            }
            return null;
        }
    }
?>