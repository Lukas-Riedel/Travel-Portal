<?php
    namespace Core\Event;

    class CompositeEvent extends Event {

        private readonly array $events;

        public function __construct(string $name, array $args, array $events) {
            parent::__construct($name, $args);
            $this->events = $events;
        }

        public function getEvents() : array {
            return $this->events;
        }
    }
?>