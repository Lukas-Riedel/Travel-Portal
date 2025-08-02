<?php
    namespace Service\Routing;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Service\Service\Authentication\AuthenticationException;
    use Service\Service\Authentication\AuthenticationService;

    class AuthMiddleware implements MiddlewareInterface {

        public const ACCESS_TOKEN_ATTRIBUTE_KEY = "accessToken";

        private const BEARER_TOKEN_PATTERN = "/Bearer\s+(.*)$/i";
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
            
            $authHeader = $request->getHeaderLine(self::AUTHORIZATION_HEADER);            
            if (!preg_match(self::BEARER_TOKEN_PATTERN, $authHeader, $matches)) {
                throw new AuthenticationException("The access token was not provided.");
            }
            $accessToken = $this->authenticationService->getAccessToken($matches[1]);            
            return $handler->handle($request->withAttribute(self::ACCESS_TOKEN_ATTRIBUTE_KEY, $accessToken));
        }
    }
?>