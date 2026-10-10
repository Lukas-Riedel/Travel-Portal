<?php
    namespace Core\Routing;

    use Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;

    class AuthorizationMiddleware implements MiddlewareInterface {

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

            $accessToken = $request->getAttribute(CommonConstants::ACCESS_TOKEN_ATTRIBUTE_KEY);
            $userInfo = $request->getAttribute(CommonConstants::USER_INFO_ATTRIBUTE_KEY);
            $userId = $request->getHeaderLine(CommonConstants::USER_ID_HEADER) ?: $userInfo->getUserId();
            $userRoles = $this->authenticationService->getUserRoles($userId, $accessToken);

            return $handler->handle($request->withAttribute(CommonConstants::USER_ROLES_ATTRIBUTE_KEY, $userRoles));
        }

    }
?>
