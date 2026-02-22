<?php
    namespace Core\OpenLineage;

    class OpenLineageEventManagerListener {

        private readonly ?OpenLineageEventManager $openLineageEventManager;

        private readonly string $coreBaseUrl;

        public function __construct(?OpenLineageEventManager $openLineageEventManager, string $coreBaseUrl) {
            $this->openLineageEventManager = $openLineageEventManager;
            $this->coreBaseUrl = $coreBaseUrl;
        }

        public function onOpenLineageEventPublished(mixed $message) : void {
            $this->openLineageEventManager?->publishEvent(new OpenLineageEvent($message["event"]["eventTime"], $message["event"]["job"]["name"],
                $message["event"]["inputs"], $message["event"]["outputs"], $this->coreBaseUrl));
        }
    }
?>