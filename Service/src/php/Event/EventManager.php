<?php
    require_once(__DIR__ . "/../Model/TargetError.php");

    class EventManager {

        private $eventHandlers;

        public function __construct($listeners = array()) {
            $this->eventHandlers = array();

            foreach ($listeners as &$listener) {
                foreach (get_class_methods($listener) as &$method) {
                    if (str_starts_with($method, "on")) {
                        $handledEvent = substr($method, 2);
                        if (!isset($this->eventHandlers[$handledEvent])) {
                            $this->eventHandlers[$handledEvent] = array();
                        }
                        $this->eventHandlers[$handledEvent][] = $listener;
                    }
                }
            }
        }

        public function getEvents($name) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT * FROM queue_event WHERE event = ?")
                ->withParameters($name)
                ->getMappedResultSet(function($event) {
                    return array(
                        "id" => $event["id"],
                        "args" => json_decode($event["args"], TRUE)
                    );
                });
        }

        public function removeEvent($eventId) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("DELETE FROM queue_event WHERE id = ?")
                ->withParameters($eventId)
                ->execute() === 1;
        }

        public function handleEvents() : void {
            global $messagingClient;

            $channel = $messagingClient->getChannel();
            $channel->basic_consume(WORKER_QUEUE_NAME, "", FALSE, FALSE, FALSE, FALSE, function ($message) {
                    $this->handleEvent(json_decode($message->getBody(), TRUE));
                    $message->ack();
                }
            );

            while (true) {
                $channel->wait();
            }
        }

        private function handleEvent($event) : void {
            global $databaseProvider, $logger;

            $start = microtime(TRUE);
            $logger->debug("Received the '" . $event["event"] . "' event...", $event);
            $databaseProvider->beginTransaction();
            try {
                $handlerMethod = "on" . $event["event"];
                foreach ($this->eventHandlers[$event["event"]] as &$eventHandler) {
                    $eventHandler->$handlerMethod($event["args"]);
                }
                $databaseProvider->commit();
            }
            catch (Throwable $e) {
                $databaseProvider->rollback();
                $error = new TargetError(400, $e, $event["args"]);
                $logger->error(basename(str_replace("\\", "/", get_class($e))) . ": " . $e->getMessage(), array("error" => $error));
            }
            finally {
                $databaseProvider->materializeViews();
                $logger->info("The '" . $event["event"] . "' event was processed in " . round((microtime(TRUE) - $start) * 1000) . " milliseconds.", $event);
                
                foreach ($logger->getHandlers() as $handler) {
                    if ($handler instanceof \Monolog\Handler\BufferHandler) {
                        $handler->flush();
                    }
                }
            }
        }
    }
?>