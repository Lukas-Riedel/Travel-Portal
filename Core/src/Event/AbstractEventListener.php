<?php
    namespace Core\Event;

    use Common\LoggingContext;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Handler\BufferHandler;
    use Monolog\Logger;

    abstract class AbstractEventListener {

        private const EVENT_HANDLER_METHOD_PREFIX = "on";
        private const OPENLINEAGE_EVENT_PUBLISHED_EVENT_NAME = "OpenLineageEventPublished";

        private readonly LoggingContext $loggingContext;
        private readonly Logger $logger;
        private readonly ?OpenLineageEventManager $openLineageEventManager;

        private readonly array $eventHandlers;

        private readonly string $workerQueueName;
        
        public function __construct(LoggingContext $loggingContext, Logger $logger, ?OpenLineageEventManager $openLineageEventManager, array $listeners, string $workerQueueName) {
            $this->loggingContext = $loggingContext;
            $this->logger = $logger;
            $this->openLineageEventManager = $openLineageEventManager;
            
            $eventHandlers = array();
            foreach ($listeners as &$listener) {
                foreach (get_class_methods($listener) as &$method) {
                    if (str_starts_with($method, self::EVENT_HANDLER_METHOD_PREFIX)) {
                        $handledEvent = substr($method, strlen(self::EVENT_HANDLER_METHOD_PREFIX));
                        $eventHandlers[$handledEvent] ??= array();
                        $eventHandlers[$handledEvent][] = $listener;
                    }
                }
            }
            $this->eventHandlers = $eventHandlers;
            $this->workerQueueName = $workerQueueName;
        }

        public abstract function listen() : void;

        protected function onEvent(mixed $event) : void {
            $start = microtime(true);
            $this->logger->pushProcessor(function($record) {
                $record["context"]["transaction_id"] = $this->loggingContext->getTransactionId();
                $record["extra"]["transaction_id"] = $this->loggingContext->getTransactionId();
                return $record;
            });
            $this->openLineageEventManager?->initializeEvent($this->workerQueueName . "/" . $event["name"]);
            $this->logger->debug("Received the '" . $event["name"] . "' event...", $event);
            try {
                $handlerMethod = self::EVENT_HANDLER_METHOD_PREFIX . $event["name"];
                foreach ($this->eventHandlers[$event["name"]] as $eventHandler) {
                    $eventHandler->$handlerMethod($event["args"]);
                }
            }
            catch (\Throwable $e) {
                $this->logger->error("The processing of the '" . $event["name"] . "' event failed. Reason: " . $e->getMessage(), array("event" => $event, "exception" => $e));
            }
            finally {
                $this->logger->info("The '" . $event["name"] . "' event was processed in " . round((microtime(true) - $start) * 1000) . " milliseconds.", $event);
                $this->flushLogger();
                $this->logger->popProcessor();

                if ($event["name"] === self::OPENLINEAGE_EVENT_PUBLISHED_EVENT_NAME) {
                    $this->openLineageEventManager?->publishCurrentEvent();
                }
                else {
                    $this->openLineageEventManager?->publishCurrentEventAsync();
                }
            }
        }

        private function flushLogger() {
            foreach ($this->logger->getHandlers() as &$handler) {
                if ($handler instanceof BufferHandler) {
                    $handler->flush();
                }
            }
        }
    }
?>