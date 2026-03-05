<?php
    namespace Core\Event;

    use Common\CommonConstants;
    use Common\LoggingContext;
    use Core\Client\Messaging\RabbitMQMessagingClient;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Exception\AMQPTimeoutException;
    use Ramsey\Uuid\Uuid;

    class RabbitMQEventListener extends AbstractEventListener {

        private const SEND_HEARTBEAT_INTERVAL_SECONDS = 30;

        private readonly RabbitMQMessagingClient $messagingClient;
        private readonly LoggingContext $loggingContext;
        private readonly Logger $logger;
        
        private readonly string $workerQueueName;
        private readonly string $consumerTag;

        private bool $isRunning = true;

        public function __construct(RabbitMQMessagingClient $messagingClient, LoggingContext $loggingContext, Logger $logger,
            ?OpenLineageEventManager $openLineageEventManager, array $listeners, string $workerQueueName) {
            parent::__construct($loggingContext, $logger, $openLineageEventManager, $listeners, $workerQueueName);
            $this->messagingClient = $messagingClient;
            $this->workerQueueName = $workerQueueName;
            $this->consumerTag = Uuid::uuid4()->toString();
            $this->loggingContext = $loggingContext;
            $this->logger = $logger;
        }

        public function listen() : void {
            $channel = $this->messagingClient->getConsumerChannel();
            $channel->queue_declare($this->workerQueueName, false, true, false, false, false, array("x-max-priority" => array("I", count(EventPriority::cases()))));
            $channel->basic_consume($this->workerQueueName, $this->consumerTag, false, false, false, false, function($message) {
                    if ($this->isRunning) {
                        $properties = $message->get_properties();
                        $headers = isset($properties["application_headers"]) ? $properties["application_headers"]->getNativeData() : array();
                        if (isset($headers[CommonConstants::TRANSACTION_ID_HEADER])) {
                            $this->loggingContext->setTransactionId($headers[CommonConstants::TRANSACTION_ID_HEADER]);
                        }
                        else {
                            $this->loggingContext->resetTransactionId();
                        }

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
                    $this->messagingClient->heartbeat();
                    $channel->wait(null, false, self::SEND_HEARTBEAT_INTERVAL_SECONDS);
                }
                catch (AMQPTimeoutException $e) {
                    continue;
                }
                catch (\Throwable $e) {
                    $this->logger->error("Unexpected error ocurred in the listener loop: " . $e->getMessage());
                    return;
                }
            }
        }
    }
?>