<?php
    namespace Common\Routing;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Slim\Psr7\Response;

    class CorsMiddleware implements MiddlewareInterface {

        private readonly array $allowedOrigins;

        public function __construct(array $allowedOrigins) {
            $this->allowedOrigins = $allowedOrigins;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            $origin = $request->getHeaderLine("Origin");            

            if (strtoupper($request->getMethod()) === "OPTIONS") {
                return (new Response())
                    ->withHeader("Access-Control-Allow-Origin", $origin)
                    ->withHeader("Access-Control-Allow-Credentials", "true")
                    ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
                    ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization")
                    ->withStatus(200);
            }

            $response = $handler->handle($request);

            if (in_array($origin, $this->allowedOrigins)) {
                $response = $response
                    ->withHeader("Access-Control-Allow-Origin", $origin)
                    ->withHeader("Access-Control-Allow-Credentials", "true")
                    ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
                    ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
            }

            return $response;
        }
    }
?>