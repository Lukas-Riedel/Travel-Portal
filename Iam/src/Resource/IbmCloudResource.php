<?php
    namespace Iam\Resource;

    use Common\Resource\AbstractResource;
    use Iam\Service\IbmCloud\IbmCloudService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class IbmCloudResource extends AbstractResource {

        private readonly IbmCloudService $ibmcloudService;

        public function __construct(IbmCloudService $ibmcloudService) {
            $this->ibmcloudService = $ibmcloudService;
        }

        public static function register(App $app, IbmCloudService $ibmcloudService) : void {
            $resource = new self($ibmcloudService);

            $app->group("/ibmcloud", function($group) use($resource) {
                $group->post("/token", [$resource, "createToken"]);
            });
        }
        public function createToken(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireServiceAccount($request);
            
            return $this->ibmcloudService->getIbmCloudAccessToken();
        }
    }
?>