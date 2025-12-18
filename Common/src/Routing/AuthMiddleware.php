<?php
    namespace Common\Routing;

    use Common\CommonConstants;
    use Common\Service\Authentication\AuthenticationException;
    use Common\Service\Authentication\AuthenticationService;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Common\Service\Authentication\UserInfo;

    class AuthMiddleware implements MiddlewareInterface {

        private const BEARER_TOKEN_PATTERN = "/Bearer\s+(.*)$/i";
        private const AUTHORIZATION_HEADER = "Authorization";

        private readonly AuthenticationService $authenticationService;
        private readonly string $basePath;
        private readonly array $whitelistedPaths;

        public function __construct(AuthenticationService $authenticationService, string $basePath, array $whitelistedPaths) {
            $this->authenticationService = $authenticationService;
            $this->basePath = $basePath;
            $this->whitelistedPaths = $whitelistedPaths;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            if ($request->getUri()->getPath() === ($this->basePath . "/") 
                || count(array_filter($this->whitelistedPaths, fn($path) => str_starts_with($request->getUri()->getPath(), $this->basePath . $path))) > 0) {
                return $handler->handle($request);
            }

            $userInfo = $this->tryExtractUserInfo($request, self::AUTHORIZATION_HEADER);
            if ($userInfo === null) {
                throw new AuthenticationException("The access token was not provided.");
            }

            return $handler->handle($request->withAttribute(CommonConstants::USER_INFO_ATTRIBUTE_KEY, $userInfo));
        }

        private function tryExtractUserInfo(ServerRequestInterface $request, string $header) : ?UserInfo {
            $authHeader = $request->getHeaderLine($header);
            if (preg_match(self::BEARER_TOKEN_PATTERN, $authHeader, $matches)) {
                return $this->authenticationService->authenticate($matches[1]);
            }
            
            return null;
        }
    }
?>
