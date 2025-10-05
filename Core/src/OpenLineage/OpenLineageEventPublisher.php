<?php
    namespace Core\OpenLineage;

    interface OpenLineageEventPublisher {
        public function publishEvent(OpenLineageEvent $event) : void;
    }
?>