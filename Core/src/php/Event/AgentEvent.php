<?php
    namespace Core\Event;

    class AgentEvent extends Event {

        public function __construct(string $name, array $args) {
            parent::__construct($name, $args);
        }
    }
?>