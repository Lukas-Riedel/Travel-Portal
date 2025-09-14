<?php
    namespace Core\Event;

    use Core\Client\MessagingClient;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Handler\BufferHandler;
    use Monolog\Logger;
    use PhpAmqpLib\Exception\AMQPTimeoutException;

    class EventManager {

        private const WAITING_FOR_MESSAGES_TIMEOUT_SECONDS = 15;

        private const EVENT_HANDLER_METHOD_PREFIX = "on";

        private readonly MessagingClient $messagingClient;
        private readonly \DatabaseProvider $databaseProvider;

        private readonly Logger $logger;
        private readonly OpenLineageEventManager $openLineageEventManager;

        private readonly array $eventHandlers;

        public function __construct(MessagingClient $messagingClient, \DatabaseProvider $databaseProvider, Logger $logger, OpenLineageEventManager $openLineageEventManager, array $listeners) {
            $this->messagingClient = $messagingClient;
            $this->databaseProvider = $databaseProvider;
            $this->logger = $logger;
            $this->openLineageEventManager = $openLineageEventManager;
            
            $eventHandlers = array();
            foreach ($listeners as &$listener) {
                foreach (get_class_methods($listener) as &$method) {
                    if (str_starts_with($method, self::EVENT_HANDLER_METHOD_PREFIX)) {
                        $handledEvent = substr($method, strlen(self::EVENT_HANDLER_METHOD_PREFIX));
                        if (!isset($eventHandlers[$handledEvent])) {
                            $eventHandlers[$handledEvent] = array();
                        }
                        $eventHandlers[$handledEvent][] = $listener;
                    }
                }
            }
            $this->eventHandlers = $eventHandlers;
        }

        public function handleEvents() : void {
            $channel = $this->messagingClient->getConsumerChannel();
            $channel->queue_declare(WORKER_QUEUE_NAME, false, true, false, false, false, array("x-max-priority" => array("I", count(EventPriority::cases()))));
            $channel->basic_consume(WORKER_QUEUE_NAME, "", false, false, false, false, function ($message) {
                    $this->handleEvent(json_decode($message->getBody(), true));
                    $message->ack();
                }
            );

            while (true) {
                try {
                    $channel->wait(null, false, self::WAITING_FOR_MESSAGES_TIMEOUT_SECONDS);
                }
                catch (AMQPTimeoutException $e) {
                    break;
                }
            }
        }

        private function handleEvent(mixed $event) : void {
            $start = microtime(true);
            $this->openLineageEventManager->initializeEvent(WORKER_QUEUE_NAME . "/" . $event["name"]);
            $this->logger->debug("Received the '" . $event["name"] . "' event...", $event);
            $this->databaseProvider->beginTransaction();
            try {
                $handlerMethod = self::EVENT_HANDLER_METHOD_PREFIX . $event["name"];
                foreach ($this->eventHandlers[$event["name"]] as $eventHandler) {
                    $eventHandler->$handlerMethod($event["args"]);
                }
                $this->databaseProvider->commit();
            }
            catch (\Throwable $e) {
                $this->databaseProvider->rollback();
                $this->logger->error("The processing of the '" . $event["name"] . "' event failed. Reason: " . $e->getMessage(), array("event" => $event, "exception" => $e));
            }
            finally {
                $this->logger->info("The '" . $event["name"] . "' event was processed in " . round((microtime(true) - $start) * 1000) . " milliseconds.", $event);
                $this->flushLogger();
                $this->openLineageEventManager->publishCurrentEventAsync();
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