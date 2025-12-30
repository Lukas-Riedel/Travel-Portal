<?php
    namespace Core\Event;

    use Core\Client\Messaging\RabbitMQMessagingClient;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Exception\AMQPTimeoutException;
    use Ramsey\Uuid\Uuid;

    class RabbitMQEventListener extends AbstractEventListener {

        private readonly RabbitMQMessagingClient $messagingClient;

        private readonly string $workerQueueName;
        private readonly string $consumerTag;

        private readonly Logger $logger;

        private bool $isRunning = true;

        public function __construct(RabbitMQMessagingClient $messagingClient, Logger $logger, ?OpenLineageEventManager $openLineageEventManager, array $listeners, string $workerQueueName) {
            parent::__construct($logger, $openLineageEventManager, $listeners, $workerQueueName);
            $this->messagingClient = $messagingClient;
            $this->workerQueueName = $workerQueueName;
            $this->consumerTag = Uuid::uuid4()->toString();;
            $this->logger = $logger;
        }

        public function listen() : void {
            $channel = $this->messagingClient->getConsumerChannel();
            $channel->queue_declare($this->workerQueueName, false, true, false, false, false, array("x-max-priority" => array("I", count(EventPriority::cases()))));
            $channel->basic_consume($this->workerQueueName, $this->consumerTag, false, false, false, false, function($message) {
                    if ($this->isRunning) {
                        $this->onEvent(json_decode($message->getBody(), true));
                        $message->ack();
                        $this->messagingClient->heartbeat();
                    }
                    else {
                        $message->nack(true); 
                    }
                }
            );

            foreach (array(SIGTERM, SIGQUIT, SIGINT) as &$signal) {
                pcntl_signal($signal, function() use($channel) {
                    $this->isRunning = false;
                    $this->logger->info("The consumer '{$this->consumerTag}' is being terminated...");

                    $channel->basic_cancel($this->consumerTag);
                });
            }

            while ($this->isRunning) {
                try {
                    $channel->wait(null, false);
                }
                catch (AMQPTimeoutException $e) {
                    $this->logger->warning("The AMQP process timed out. Reason: " . $e->getMessage());
                    return;
                }
            }
        }
    }
?>