<?php
    namespace Service\Routing;

    use Monolog\Logger;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Service\Routing\NotFoundException;
    use Slim\Psr7\Response;
    use Service\Service\Authentication\AuthenticationException;

    class ErrorHandlingMiddleware implements MiddlewareInterface {

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            try {
                return $handler->handle($request);
            }
            catch (\Throwable $e) {
                $code = $this->getErrorCode($e);
                
                $requestError = new RequestError($code, basename(str_replace("\\", "/", get_class($e))), $e->getMessage(), explode("\n", $e->getTraceAsString()), 
                    $request->getUri()->getPath());

                $response = new Response($code);
                $response->getBody()->write(json_encode($requestError, JSON_UNESCAPED_UNICODE));
                $this->logger->error($requestError->getType() . ": " . $requestError->getMessage(), array("error" => $requestError));

                return $response->withHeader("Content-Type", "application/json");
            }
        }

        private function getErrorCode(\Throwable $e) : int {
            if ($e instanceof NotFoundException) {
                return 404;
            }
            if ($e instanceof AuthenticationException) {
                return 401;
            }
            if ($e instanceof AuthorizationException) {
                return 403;
            }
            return 400;
        }
    }
?>