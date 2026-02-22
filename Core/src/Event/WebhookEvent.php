<?php
    namespace Core\Event;

    class WebhookEvent extends Event {

        private readonly int $ttl;

        public function __construct(Event|string $eventOrName, int $ttl, array $args = array()) {
            if ($eventOrName instanceof Event) {
                parent::__construct($eventOrName->getName(), $eventOrName->getArgs());
            }
            else {
                parent::__construct($eventOrName, $args);
            }
            $this->ttl = $ttl;
        }

        public function getTtl() : int {
            return $this->ttl;
        }
    }
?>