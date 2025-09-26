<?php
    namespace Core\Event;

    class WebhookEvent extends Event {

        private readonly int $ttl;

        public function __construct(Event $event, int $ttl) {
            parent::__construct($event->getName(), $event->getArgs());
            $this->ttl = $ttl;
        }

        public function getTtl() : int {
            return $this->ttl;
        }
    }
?>