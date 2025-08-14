<?php
    namespace Core\Routing;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Core\Service\Authentication\AccessToken;
    use Core\Service\Authentication\AuthenticationException;
    use Core\Service\Authentication\AuthenticationService;

    class AuthMiddleware implements MiddlewareInterface {

        public const ACCESS_TOKEN_ATTRIBUTE_KEY = "accessToken";

        private const BEARER_TOKEN_PATTERN = "/Bearer\s+(.*)$/i";
        private const GOOG_CHANNEL_TOKEN_HEADER = "X-Goog-Channel-Token";
        private const AUTHORIZATION_HEADER = "Authorization";

        private readonly AuthenticationService $authenticationService;
        private readonly string $basePath;

        public function __construct(AuthenticationService $authenticationService, string $basePath) {
            $this->authenticationService = $authenticationService;
            $this->basePath = $basePath;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            if ($request->getUri()->getPath() === ($this->basePath . "/") 
                || str_starts_with($request->getUri()->getPath(), $this->basePath . "/swagger")) {
                return $handler->handle($request);
            }

            $accessToken = $this->tryExtractAccessToken($request, self::AUTHORIZATION_HEADER);
            if ($accessToken === null) {                
                $accessToken = $this->tryExtractAccessToken($request, self::GOOG_CHANNEL_TOKEN_HEADER);
            }            
            if ($accessToken === null) {
                throw new AuthenticationException("The access token was not provided.");
            }

            return $handler->handle($request->withAttribute(self::ACCESS_TOKEN_ATTRIBUTE_KEY, $accessToken));
        }

        private function tryExtractAccessToken(ServerRequestInterface $request, string $header) : ?AccessToken {
            $authHeader = $request->getHeaderLine($header);
            if (preg_match(self::BEARER_TOKEN_PATTERN, $authHeader, $matches)) {
                return $this->authenticationService->getAccessToken($matches[1]);
            }
            return null;
        }
    }
?>