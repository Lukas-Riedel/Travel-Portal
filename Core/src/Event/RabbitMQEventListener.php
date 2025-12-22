<?php
    namespace Core\Event;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Messaging\RabbitMQMessagingClient;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Exception\AMQPTimeoutException;

    class RabbitMQEventListener extends AbstractEventListener {

        // TODO: Make configurable in the deployment.
        private const WAITING_FOR_MESSAGES_TIMEOUT_SECONDS = 30;

        private readonly RabbitMQMessagingClient $messagingClient;

        private readonly string $workerQueueName;

        public function __construct(RabbitMQMessagingClient $messagingClient, Logger $logger, ?OpenLineageEventManager $openLineageEventManager, array $listeners, string $workerQueueName) {
            parent::__construct($logger, $openLineageEventManager, $listeners, $workerQueueName);
            $this->messagingClient = $messagingClient;
            $this->workerQueueName = $workerQueueName;
        }

        public function listen() : void {
            $channel = $this->messagingClient->getConsumerChannel();
            $channel->queue_declare($this->workerQueueName, false, true, false, false, false, array("x-max-priority" => array("I", count(EventPriority::cases()))));
            $channel->basic_consume($this->workerQueueName, "", false, false, false, false, function($message) {
                    $this->onEvent(json_decode($message->getBody(), true));
                    $message->ack();
                    $this->messagingClient->heartbeat();
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