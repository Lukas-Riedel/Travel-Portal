<?php
    namespace Core\Client\Messaging;

    use Core\Event\Event;
    use Core\Event\EventPriority;

    interface MessagingClient extends ProgressReporter {
        public function publish(string $queueName, Event $event, ?EventPriority $eventPriority = null) : void;
    }
?>