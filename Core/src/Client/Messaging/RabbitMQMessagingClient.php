<?php
    namespace Core\Client\Messaging;

    use Common\Client\HealthCheckable;
    use Common\CommonConstants;
    use Common\LoggingContext;
    use Core\Client\Database\TransactionManager;
    use Core\Event\Event;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Channel\AMQPChannel;
    use PhpAmqpLib\Connection\AMQPStreamConnection;
    use PhpAmqpLib\Message\AMQPMessage;
    use PhpAmqpLib\Wire\AMQPTable;

    class RabbitMQMessagingClient implements MessagingClient, HealthCheckable {

        private const SEND_HEARTBEAT_THRESHOLD_SECONDS = 10;

        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "rmq://%s@%s:%s/%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s/%s";

        private readonly string $host;
        private readonly string $port;
        private readonly string $vhost;
        private readonly string $user;
        private readonly string $password;
        private readonly int $heartbeatSeconds;
        private readonly int $prefetchCount;

        private readonly TransactionManager $transactionManager;

        private ?AMQPStreamConnection $connection;
        private ?AMQPChannel $producerChannel;
        private ?AMQPChannel $consumerChannel;

        private readonly LoggingContext $loggingContext;
        private readonly Logger $logger;
        private ?OpenLineageEventManager $openLineageEventManager;

        private ?int $lastHeartbeatTimestamp;

        public function __construct(string $host, string $port, string $vhost, string $user, string $password, int $heartbeatSeconds, int $prefetchCount,
            TransactionManager $transactionManager, LoggingContext $loggingContext, Logger $logger) {
            $this->host = $host;
            $this->port = $port;
            $this->vhost = $vhost;
            $this->user = $user;
            $this->password = $password;
            $this->heartbeatSeconds = $heartbeatSeconds;
            $this->prefetchCount = $prefetchCount;
            $this->connection = null;
            $this->producerChannel = null;
            $this->consumerChannel = null;
            $this->loggingContext = $loggingContext;
            $this->logger = $logger;
            $this->transactionManager = $transactionManager;
            $this->openLineageEventManager = null;
            $this->lastHeartbeatTimestamp = null;
        }

        public function __destruct() {
            if ($this->producerChannel !== null) {
                $this->producerChannel->close();
            }
            if ($this->consumerChannel !== null) {
                $this->consumerChannel->close();
            }
            if ($this->connection !== null) {
                $this->connection->close();
            }
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function getServiceName() : string {
            return "rabbitmq";
        }

        public function isHealthy() : bool {
            try {
                $this->init();
                return $this->connection->isConnected();
            }
            catch (\Throwable $e) {
                return false;
            }
        }

        public function heartbeat() : void {
            if ($this->connection !== null) {
                $secondsSinceLastHeartbeat = time() - ($this->lastHeartbeatTimestamp ?? 0);

                if ($secondsSinceLastHeartbeat > self::SEND_HEARTBEAT_THRESHOLD_SECONDS) {
                    $this->connection->checkHeartBeat();
                    $this->lastHeartbeatTimestamp = time();
                }
            }
        }

        public function publish(string $queueName, Event $event, ?EventPriority $eventPriority = null) : void {
            $atomicExecution = $this->transactionManager->getCurentAtomicExecution();
            if ($atomicExecution !== null) {
                // Delay publishing to the end of the transaction.
                $atomicExecution->addAfterCommitCallback(function() use(&$queueName, &$event, &$eventPriority) { 
                    $this->doPublish($queueName, $event, $eventPriority); 
                });
            }
            else {
                $this->doPublish($queueName, $event, $eventPriority);
            }
        }

        private function doPublish(string $queueName, Event $event, ?EventPriority $eventPriority = null) : void {
            $this->init();

            $json = json_encode($event, JSON_UNESCAPED_UNICODE);

            $messageHeaders = array("content_type" => "application/json");
            if ($eventPriority !== null) {
                $messageHeaders["priority"] = $eventPriority->value;
            }

            $transactionId = $this->loggingContext->getTransactionId();
            if ($transactionId !== null) {
                $messageHeaders["application_headers"] = new AMQPTable(array(CommonConstants::TRANSACTION_ID_HEADER => $transactionId));
            }

            if ($this->openLineageEventManager !== null) {
                $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $this->user, $this->host, $this->port, $this->vhost);
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $queueName, $event->getName());
                $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $name, $event->getArgs());
            }

            $this->logger->debug("Publishing the '" . $event->getName() . "' event to RabbitMQ...", json_decode($json, true));

            $this->producerChannel->basic_publish(new AMQPMessage($json, $messageHeaders), "", $queueName);
        }

        public function getConsumerChannel() : AMQPChannel {
            $this->init();
            
            return $this->consumerChannel;
        }

        private function init() : void {
            if ($this->connection === null || $this->producerChannel === null) {                    
                $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost,
                    false, "AMQPLAIN", null, "en_US", round(1.2 * $this->heartbeatSeconds), round(1.2 * $this->heartbeatSeconds),
                    null, true, $this->heartbeatSeconds);
                $this->producerChannel = $this->connection->channel();
                $this->consumerChannel = $this->connection->channel();
                $this->consumerChannel->basic_qos(null, $this->prefetchCount, null);
                $this->lastHeartbeatTimestamp = time();
            }
        }
    }
?>