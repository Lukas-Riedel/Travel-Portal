<?php
    namespace Core\Client;

    use Core\Event\Event;
    use Core\Event\EventPriority;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PhpAmqpLib\Channel\AMQPChannel;
    use PhpAmqpLib\Connection\AMQPSSLConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    class MessagingClient {

        private const HEARTBEAT_INTERVAL_SECONDS = 60;

        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "rmq://%s@%s:%s/%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s/%s";

        private ?AMQPSSLConnection $connection = null;
        private ?AMQPChannel $producerChannel = null;
        private ?AMQPChannel $consumerChannel = null;

        private readonly Logger $logger;
        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(Logger $logger) {
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

            $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, RMQ_USER, RMQ_HOST, RMQ_PORT, RMQ_VHOST);
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
                $this->connection = new AMQPSSLConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PW, RMQ_VHOST,
                    array("verify_peer" => true, "verify_peer_name" => true),
                    array("read_write_timeout" => self::HEARTBEAT_INTERVAL_SECONDS, "heartbeat" => self::HEARTBEAT_INTERVAL_SECONDS));
                $this->producerChannel = $this->connection->channel();
                $this->consumerChannel = $this->connection->channel();
                $this->consumerChannel->basic_qos(null, 1, null);
            }
        }
    }
?>