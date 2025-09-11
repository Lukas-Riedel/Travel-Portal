<?php
    namespace Core\OpenLineage;

    class OpenLineageEventManagerListener {

        private readonly OpenLineageEventManager $openLineageEventManager;

        public function __construct(OpenLineageEventManager $openLineageEventManager) {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function onOpenLineageEventPublished(mixed $message) : void {
            $this->openLineageEventManager->publishEvent(new OpenLineageEvent($message["event"]["job"]["name"], $message["event"]["inputs"], $message["event"]["outputs"]));
        }
    }
?>