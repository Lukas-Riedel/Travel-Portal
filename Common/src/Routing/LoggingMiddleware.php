<?php
    namespace Common\Routing;

    use Common\CommonConstants;
    use Common\LoggingContext;
    use Monolog\Logger;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;

    class LoggingMiddleware implements MiddlewareInterface {

        private readonly LoggingContext $loggingContext;
        private readonly Logger $logger;

        public function __construct(LoggingContext $loggingContext, Logger $logger) {
            $this->loggingContext = $loggingContext;
            $this->logger = $logger;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {            
            $start = microtime(true);
            $transactionIdHeader = $request->getHeaderLine(CommonConstants::TRANSACTION_ID_HEADER);
            if ($transactionIdHeader !== "") {
                $this->loggingContext->setTransactionId($transactionIdHeader);
            }
            else {
                $this->loggingContext->resetTransactionId();
            }

            $this->logger->pushProcessor(function($record) {
                $record["context"]["transaction_id"] = $this->loggingContext->getTransactionId();
                $record["extra"]["transaction_id"] = $this->loggingContext->getTransactionId();
                return $record;
            });       

            try {
                $this->logger->debug("Received the '" . $this->formatRequest($request) . "' request...");
                $response = $handler->handle($request);                
                $this->logger->info("The '" . $this->formatRequest($request) . "' request was processed in " . round((microtime(true) - $start) * 1000) . " milliseconds.");
                return $response;
            }
            finally {
                $this->logger->popProcessor();
            }
        }

        private function formatRequest(ServerRequestInterface $request) : string {
            return $request->getMethod() . " " . $request->getUri()->getPath() . ($request->getUri()->getQuery() ? "?" . $request->getUri()->getQuery() : "");
        }
    }
?>