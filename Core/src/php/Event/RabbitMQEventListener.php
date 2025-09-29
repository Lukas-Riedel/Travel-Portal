<?php
    namespace Core\Event;

    use Core\Client\Messaging\RabbitMQMessagingClient;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Exception\AMQPTimeoutException;

    class RabbitMQEventListener extends AbstractEventListener {

        private const WAITING_FOR_MESSAGES_TIMEOUT_SECONDS = 15;

        private readonly RabbitMQMessagingClient $messagingClient;

        public function __construct(RabbitMQMessagingClient $messagingClient, Logger $logger, OpenLineageEventManager $openLineageEventManager, array $listeners) {
            parent::__construct($logger, $openLineageEventManager, $listeners);
            $this->messagingClient = $messagingClient;
        }

        public function listen() : void {
            $channel = $this->messagingClient->getConsumerChannel();
            $channel->queue_declare(WORKER_QUEUE_NAME, false, true, false, false, false, array("x-max-priority" => array("I", count(EventPriority::cases()))));
            $channel->basic_consume(WORKER_QUEUE_NAME, "", false, false, false, false, function ($message) {
                    $this->onEvent(json_decode($message->getBody(), true));
                    $message->ack();
                }
            );

            while (true) {
                try {
                    $channel->wait(null, false, self::WAITING_FOR_MESSAGES_TIMEOUT_SECONDS);
                }
                catch (AMQPTimeoutException $e) {
                    break;
                }
            }
        }
    }
?>