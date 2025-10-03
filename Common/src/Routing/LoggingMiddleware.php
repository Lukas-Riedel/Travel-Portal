<?php
    namespace Common\Routing;

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
            $start = microtime(true);
            $this->logger->debug("Received the '" . $this->formatRequest($request) . "' request...", array("headers" => $request->getHeaders(), "payload" => $request->getParsedBody()));
            $response = $handler->handle($request);
            $this->logger->info("The '" . $this->formatRequest($request) . "' request was processed in " . round((microtime(true) - $start) * 1000) . " milliseconds.", array("headers" => $request->getHeaders(), "payload" =>$request->getParsedBody()));
            return $response;
        }

        private function formatRequest(ServerRequestInterface $request) : string {
            return $request->getMethod() . " " . $request->getUri()->getPath() . ($request->getUri()->getQuery() ? "?" . $request->getUri()->getQuery() : "");
        }
    }
?>