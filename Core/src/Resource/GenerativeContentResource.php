<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Service\Configuration\ConfigurationService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class GenerativeContentResource extends AbstractResource {

        private readonly GenerativeContentClient $generativeContentClient;
        private readonly ConfigurationService $configurationService;

        public function __construct(GenerativeContentClient $generativeContentClient, ConfigurationService $configurationService) {
            $this->generativeContentClient = $generativeContentClient;
            $this->configurationService = $configurationService;
        }

        public static function register(App $app, GenerativeContentClient $generativeContentClient, ConfigurationService $configurationService) : void {
            $resource = new self($generativeContentClient, $configurationService);

            $app->group("/generativecontent", function($group) use($resource) {
                $group->post("", [$resource, "createGenerativeContent"]);
            });
        }

        public function createGenerativeContent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireBackendServiceAccount($request);
            
            $promptTemplate = $this->requireJsonBodyField($request, "promptTemplate");
            $context = $this->getJsonBodyField($request, "context") ?? array();

            $generativeContentPrompts = $this->configurationService->getConfigurationEntry("generativeContentPrompts");
            if (!isset($generativeContentPrompts[$promptTemplate])) {
                throw new \InvalidArgumentException("The prompt template '{$promptTemplate}' does not exist.");
            }

            return $this->generativeContentClient->getResponse($generativeContentPrompts[$promptTemplate], $context);
        }
    }
?>