<?php
    namespace Common\Routing;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Slim\Interfaces\InvocationStrategyInterface;

    class JsonInvocationStrategy implements InvocationStrategyInterface {

        public function __invoke(callable $callable, ServerRequestInterface $request,
            ResponseInterface $response, array $routeArguments) : ResponseInterface {
            $result = $callable($request, $response, $routeArguments);

            if ($result instanceof ResponseInterface) {
                $response = $result;
            }
            else if ($result === null) {
                $response = $response->withStatus(204);
            }
            else {
                $response->getBody()->write(json_encode($this->filter($result) ?? array(), JSON_UNESCAPED_UNICODE));
                $response = $response
                    ->withStatus($this->isCreateRequest($request, $callable) ? 201 : 200)
                    ->withHeader("Content-Type", "application/json");
            }

            return $response
                ->withHeader("Cache-Control", "no-cache, no-store, must-revalidate")
                ->withHeader("Pragma", "no-cache")
                ->withHeader("Expires", "0");
        }
        
        private function isCreateRequest(ServerRequestInterface $request, callable $callable) : bool {
            if ($request->getMethod() !== "POST") {
                return false;
            }
            
            $methodName = is_array($callable) ? ($callable[1] ?? "") : "";
            return str_starts_with($methodName, "create");
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