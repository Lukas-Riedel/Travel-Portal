<?php
    namespace Service\Routing;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Slim\Interfaces\InvocationStrategyInterface;

    class JsonInvocationStrategy implements InvocationStrategyInterface {

        public function __invoke(callable $callable, ServerRequestInterface $request,
            ResponseInterface $response, array $routeArguments) : ResponseInterface {
            $result = $callable($request, $response, $routeArguments);
            if ($result === NULL) {
                $response = $response->withStatus(204);
            }
            else {
                $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
                $response = $response
                    // TODO: Remove this workaround for future use cases of POST endpoints returning other codes than 201.
                    ->withStatus($request->getMethod() === "POST" ? 201 : 200)
                    ->withHeader("Content-Type", "application/json");
            }
            return $response
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader("Cache-Control", "no-cache, no-store, must-revalidate")
                ->withHeader("Pragma", "no-cache")
                ->withHeader("Expires", "0");
        }
    }
?>