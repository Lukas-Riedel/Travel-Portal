<?php
    namespace Core\Client\CloudMessaging;

    use Core\Event\Event;

    interface CloudMessagingClient {
        public function publish(Event $event, array $deviceTokens) : void;
    }
?>