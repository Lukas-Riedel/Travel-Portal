<?php
    namespace Core\Client\Messaging;

    use Core\Event\Event;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Channel\AMQPChannel;
    use PhpAmqpLib\Connection\AMQPSSLConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    class RabbitMQMessagingClient implements MessagingClient {

        private const HEARTBEAT_INTERVAL_SECONDS = 90;
        private const PREFETCH_COUNT = 1;

        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "rmq://%s@%s:%s/%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s/%s";

        private readonly string $host;
        private readonly string $port;
        private readonly string $vhost;
        private readonly string $user;
        private readonly string $password;

        private ?AMQPSSLConnection $connection = null;
        private ?AMQPChannel $producerChannel = null;
        private ?AMQPChannel $consumerChannel = null;

        private readonly Logger $logger;
        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(string $host, string $port, string $vhost, string $user, string $password, Logger $logger) {
            $this->host = $host;
            $this->port = $port;
            $this->vhost = $vhost;
            $this->user = $user;
            $this->password = $password;
            $this->logger = $logger;
            $this->openLineageEventManager = null;
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

        public function publish(string $queueName, Event $event, ?EventPriority $eventPriority = null) : void {
            $this->init();

            $json = json_encode($event, JSON_UNESCAPED_UNICODE);

            $messageHeaders = array("content_type" => "application/json");
            if ($eventPriority !== null) {
                $messageHeaders["priority"] = $eventPriority->value;
            }

            $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $this->user, $this->host, $this->port, $this->vhost);
            $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $queueName, $event->getName());
            $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $name, $event->getArgs());

            $this->logger->debug("Publishing the '" . $event->getName() . "' event to RabbitMQ...", json_decode($json, true));

            $this->producerChannel->basic_publish(new AMQPMessage($json, $messageHeaders), "", $queueName);
        }

        public function getConsumerChannel() : AMQPChannel {
            $this->init();
            return $this->consumerChannel;
        }

        private function init() {
            if ($this->connection === null || $this->producerChannel === null) {                    
                $this->connection = new AMQPSSLConnection($this->host, $this->port, $this->user, $this->password, $this->vhost,
                    array("verify_peer" => true, "verify_peer_name" => true),
                    array("read_write_timeout" => round(1.2 * self::HEARTBEAT_INTERVAL_SECONDS), "heartbeat" => self::HEARTBEAT_INTERVAL_SECONDS));
                $this->producerChannel = $this->connection->channel();
                $this->consumerChannel = $this->connection->channel();
                $this->consumerChannel->basic_qos(null, self::PREFETCH_COUNT, null);
            }
        }
    }
?>