<?php
    namespace Core\Event;

    class CompositeEvent extends Event {
        private array $events;

        public function __construct(string $name, array $args) {
            parent::__construct($name, $args);
            $this->events = array();
        }

        public function addAgentEvent(string $agentId) : CompositeEvent {
            $this->events[] = new AgentEvent($this->getName(), $agentId, $this->getArgs());
            return $this;
        }

        public function addCloudMessagingEvent(array $requiredRoles, array $supportedDeviceTypes) : CompositeEvent {
            $this->events[] = new CloudMessagingEvent($this->getName(), $requiredRoles, $supportedDeviceTypes, $this->getArgs());
            return $this;
        }

        public function addWebhookEvent(int $ttl) : CompositeEvent {
            $this->events[] = new WebhookEvent($this->getName(), $ttl, $this->getArgs());
            return $this;
        }

        public function addWorkerEvent(EventPriority $priority) : CompositeEvent {
            $this->events[] = new WorkerEvent($this->getName(), $priority, $this->getArgs());
            return $this;
        }

        public function addCortexEvent() : CompositeEvent {
            $this->events[] = new CortexEvent($this->getName(), $this->getArgs());
            return $this;
        }

        public function getEvents() : array {
            return $this->events;
        }
    }
?>