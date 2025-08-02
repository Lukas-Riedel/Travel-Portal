<?php
    namespace Service\Routing;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Slim\Interfaces\InvocationStrategyInterface;

    class JsonInvocationStrategy implements InvocationStrategyInterface {

        public function __invoke(callable $callable, ServerRequestInterface $request,
            ResponseInterface $response, array $routeArguments) : ResponseInterface {
            $result = $callable($request, $response, $routeArguments);
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader("Cache-Control", "no-cache, no-store, must-revalidate")
                ->withHeader("Pragma", "no-cache")
                ->withHeader("Expires", "0")
                ->withHeader("Content-Type", "application/json");
        }
    }
?>