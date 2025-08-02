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
            while (($event = $this->getNextPendingEvent()) !== NULL) {
                $this->handleEvent($event);
            }
        }

        private function handleEvent($event) : void {
            global $databaseProvider, $loggingProvider;

            $databaseProvider->beginTransaction();
            try {
                $handlerMethod = "on" . $event["name"];
                foreach ($this->eventHandlers[$event["name"]] as &$eventHandler) {
                    $eventHandler->$handlerMethod($event["args"]);
                }
                $databaseProvider->commit();
            }
            catch (Throwable $e) {
                $databaseProvider->rollback();
                $error = new TargetError(400, $e, $event["args"]);
                $loggingProvider->logError(json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG));
            }
            finally {
                $databaseProvider->materializeViews();
                $this->removeEvent($event["id"]);
            }

            if ($event["name"] == Event::ApplicationStarted->name) {
                die("Restaring the application...");
            }
        }

        private function getNextPendingEvent() : mixed {
            global $databaseProvider;
    
            $nextEvent = $databaseProvider
                ->statementBuilder("SELECT * FROM queue_event WHERE FIND_IN_SET(event, ?) ORDER BY priority ASC LIMIT 1")
                ->withParameters(implode(",", array_keys($this->eventHandlers)))
                ->getSingleRow();
    
            if ($nextEvent === NULL) {    
                return NULL;
            }
    
            return array(
                "id" => $nextEvent["id"],
                "name" => $nextEvent["event"],
                "args" => json_decode($nextEvent["args"], TRUE)
            );
        }
    }
?>