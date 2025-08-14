<?php
    namespace Core\Resource;

    use OpenApi\Generator;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Slim\App;
    use Slim\Handlers\Strategies\RequestResponse;

    class SwaggerResource extends AbstractResource {
        public static function register(App $app) {
            $app->get("/swagger/swagger.json", function (ServerRequestInterface $request, ResponseInterface $response) {
                $openapi = (new Generator())->generate(array(
                    __DIR__ . "/../Resource/", 
                    __DIR__ . "/../Service/", 
                    __DIR__ . "/../OpenAPI/", 
                    __DIR__ . "/../Routing/"
                ));
                $response->getBody()->write($openapi->toJson());
                return $response->withHeader("Content-Type", "application/json");
            })->setInvocationStrategy(new RequestResponse());
        }
    }
?>