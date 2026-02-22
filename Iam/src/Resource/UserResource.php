<?php
    namespace Iam\Resource;

    use Common\Resource\AbstractResource;
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
            });
        }
        public function listUsers(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireBackendServiceAccount($request);
            
            $role = $this->requireQueryParameter($request, "role");

            return $this->userService->getUserIdsWithRole(UserRole::from($role));
        }
    }
?>