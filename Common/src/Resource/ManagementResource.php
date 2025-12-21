<?php
    namespace Common\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class ManagementResource extends AbstractResource {

        private const SERVICE_UP = "up";
        private const SERVICE_DOWN = "down";
        
        private readonly string $serviceName;
        private readonly string $serviceVersion;
        private readonly array $healthCheckables;

        public function __construct(string $serviceName, string $serviceVersion, array $healthCheckables) {
            $this->serviceName = $serviceName;
            $this->serviceVersion = $serviceVersion;
            $this->healthCheckables = $healthCheckables;
        }

        public static function register(App $app, string $serviceName, string $serviceVersion, array $healthCheckables) : void {
            $resource = new self($serviceName, $serviceVersion, $healthCheckables);

            $app->group("/management", function($group) use($resource) {
                $group->get("/liveness", [$resource, "checkLiveness"]);
                $group->get("/readiness", [$resource, "checkReadiness"]);
            });
        }

        public function checkLiveness(Request $request, Response $response, array $routeArguments) : mixed {
            $content = array(
                "name" => $this->getFormattedServiceName(),
                "version" => $this->serviceVersion,
                "status" => self::SERVICE_UP
            );
            
            $response->getBody()->write(json_encode($content));
            return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(200);
        }

        public function checkReadiness(Request $request, Response $response, array $routeArguments) : mixed {
            $isReady = true;
            $dependencies = array();
            foreach ($this->healthCheckables as $healthCheckable) {
                $isHealthy = $healthCheckable->isHealthy();
                $isReady &= $isHealthy;
                $dependencies[$healthCheckable->getServiceName()] = array(
                    "status" => $isHealthy ? self::SERVICE_UP : self::SERVICE_DOWN
                );
            }

            $content = array(
                "name" => $this->getFormattedServiceName(),
                "version" => $this->serviceVersion,
                "status" => self::SERVICE_UP,
                "dependencies" => $dependencies
            );
            
            $response->getBody()->write(json_encode($content));
            return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus($isReady ? 200 : 503);
        }

        private function getFormattedServiceName() : string {
            $tokens = explode("/", $this->serviceName);
            return $tokens[count($tokens) - 1];
        }
    }
?>