<?php
    namespace Service\Routing;

    use Monolog\Logger;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;

    class LoggingMiddleware implements MiddlewareInterface {

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            $start = microtime(TRUE);
            $this->logger->info("Received " . $request->getMethod() . " request to '" . $request->getUri()->getPath() . "'...");
            $response = $handler->handle($request);
            $this->logger->info("The " . $request->getMethod() . " request to '" . $request->getUri()->getPath() . "' was processed in " . round((microtime(TRUE) - $start) * 1000) . " milliseconds.");
            return $response;
        }
    }
?>