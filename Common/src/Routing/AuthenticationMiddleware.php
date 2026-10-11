<?php
    namespace Common\Routing;

    use Common\CommonConstants;
    use Common\Service\Authentication\AuthenticationException;
    use Common\Service\Authentication\AuthenticationService;
    use Common\Service\Authentication\UserInfo;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;

    class AuthenticationMiddleware implements MiddlewareInterface {

        private const BEARER_TOKEN_PATTERN = "/Bearer\s+(.*)$/i";
        private const AUTHORIZATION_HEADER = "Authorization";

        private readonly AuthenticationService $authenticationService;
        
        private readonly string $basePath;
        private readonly array $whitelistedPaths;
        private readonly string $iamBackendClientId;

        public function __construct(AuthenticationService $authenticationService, string $basePath, array $whitelistedPaths, string $iamBackendClientId) {
            $this->authenticationService = $authenticationService;
            $this->basePath = $basePath;
            $this->whitelistedPaths = $whitelistedPaths;
            $this->iamBackendClientId = $iamBackendClientId;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            if ($request->getUri()->getPath() === ($this->basePath . "/")
                || count(array_filter($this->whitelistedPaths, fn($path) => str_starts_with($request->getUri()->getPath(), $this->basePath . $path))) > 0) {
                return $handler->handle($request);
            }

            $authHeader = $request->getHeaderLine(self::AUTHORIZATION_HEADER);
            if (!preg_match(self::BEARER_TOKEN_PATTERN, $authHeader, $matches)) {
                throw new AuthenticationException("The access token was not provided.");
            }

            $accessToken = $matches[1];
            $userInfo = $this->authenticationService->authenticate($accessToken);

            if ($userInfo->getClient() === $this->iamBackendClientId) {
                $userId = $request->getHeaderLine(CommonConstants::USER_ID_HEADER) ?: $userInfo->getUserId();
                $username = $request->getHeaderLine(CommonConstants::USERNAME_HEADER) ?: $userInfo->getUsername();

                $userInfo = new UserInfo($userId, $username, $userInfo->getClient(), $userId !== $userInfo->getUserId());
            }

            return $handler->handle($request->withAttribute(CommonConstants::USER_INFO_ATTRIBUTE_KEY, $userInfo)
                ->withAttribute(CommonConstants::ACCESS_TOKEN_ATTRIBUTE_KEY, $accessToken));
        }

    }
?>
