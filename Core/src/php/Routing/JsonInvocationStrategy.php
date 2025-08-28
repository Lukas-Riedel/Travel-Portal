<?php
    namespace Core\Routing;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Slim\Interfaces\InvocationStrategyInterface;

    class JsonInvocationStrategy implements InvocationStrategyInterface {

        public function __invoke(callable $callable, ServerRequestInterface $request,
            ResponseInterface $response, array $routeArguments) : ResponseInterface {
            $result = $callable($request, $response, $routeArguments);
            if ($result === null) {
                $response = $response->withStatus(204);
            }
            else {
                $response->getBody()->write(json_encode($this->filter($result) ?? array(), JSON_UNESCAPED_UNICODE));
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
        
        private function filter(mixed $value) : mixed {
            $decoded = json_decode(json_encode($value, JSON_UNESCAPED_UNICODE), true);
            if (!is_array($decoded)) {
                return $decoded;
            }

            $newValue = array();
            foreach ($decoded as $key => $v) {
                $v = $this->filter($v);
                if ($v !== null) {
                    $newValue[$key] = $v;                    
                }
            }
            return count($newValue) === 0 ? null : $newValue;
        }
    }
?>