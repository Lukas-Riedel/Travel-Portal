<?php
    namespace Iam\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\AuthorizationException;
    use Common\Service\Authentication\UserRole;
    use Iam\Service\User\UserService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class UserResource extends AbstractResource {

        private readonly UserService $userService;

        public function __construct(UserService $userService) {
            $this->userService = $userService;
        }

        public static function register(App $app, UserService $userService) : void {
            $resource = new self($userService);

            $app->group("/users", function($group) use($resource) {
                $group->get("", [$resource, "listUsers"]);
                $group->get("/{userId}/roles", [$resource, "listUserRoles"]);
            });
        }
        
        public function listUsers(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireBackendServiceAccount($request);
            
            $role = $this->requireQueryParameter($request, "role");

            return $this->userService->getUserIdsWithRole(UserRole::from($role));
        }

        public function listUserRoles(Request $request, Response $response, array $routeArguments) : mixed {
            $userId = $this->requirePathArgument($routeArguments, "userId");

            $userInfo = $this->getUserInfo($request);
            if (!$this->isBackendServiceAccount($request) && $userInfo->getUserId() !== $userId) {
                throw new AuthorizationException($userInfo);
            }

            return $this->userService->getUserRoles($userId);
        }
    }
?>