<?php
    namespace Core\Service\Document;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class DocumentServiceListener {

        private const SEND_DOCUMENT_NOTIFICATIONS_ACTION_NAME = "SEND_DOCUMENT_NOTIFICATIONS";
        private const SEND_DOCUMENT_NOTIFICATIONS_ACTION_INTERVAL = 300;

        private readonly DocumentService $documentService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly int $notificationThreshold;

        public function __construct(DocumentService $documentService, EventPublisher $eventPublisher, Scheduler $scheduler, int $notificationThreshold) {
            $this->documentService = $documentService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->notificationThreshold = $notificationThreshold;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::SEND_DOCUMENT_NOTIFICATIONS_ACTION_NAME, self::SEND_DOCUMENT_NOTIFICATIONS_ACTION_INTERVAL)) {
                foreach ($this->documentService->getDocumentsForNotifications($this->notificationThreshold) as &$document) {
                    $this->eventPublisher->publish(Event::DocumentExpiring($document->getName(), $document->getExpiration()));
                }
            }
        }
    }
?>
