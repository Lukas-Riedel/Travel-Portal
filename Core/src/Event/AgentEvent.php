<?php
    namespace Core\Event;

    class AgentEvent extends Event {

        private readonly string $agentId;

        public function __construct(string $name, string $agentId, array $args) {
            parent::__construct($name, $args);
            $this->agentId = $agentId;
        }

        public function getAgentId() : string {
            return $this->agentId;
        }
    }
?>