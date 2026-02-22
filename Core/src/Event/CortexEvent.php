<?php
    namespace Core\Event;

    class CortexEvent extends Event {
        
        private readonly EventPriority $priority;

        public function __construct(string $name, EventPriority $priority, array $args) {
            parent::__construct($name, $args);
            $this->priority = $priority;
        }

        public function getPriority() : EventPriority {
            return $this->priority;
        }
    }
?>