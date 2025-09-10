<?php
    namespace Core\Client;

    use Monolog\Logger;
    use PhpAmqpLib\Channel\AMQPChannel;
    use PhpAmqpLib\Connection\AMQPSSLConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    class MessagingClient {

        private const MAX_PRIORITY = 5;
        private const HEARTBEAT_INTERVAL_SECONDS = 60;

        private ?AMQPSSLConnection $connection = null;
        private ?AMQPChannel $producerChannel = null;
        private ?AMQPChannel $consumerChannel = null;

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
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

        public function publishEvent(\Event $event, ?array $args, string $queueName) : void {
            $this->init();
            $this->consumerChannel->queue_declare($queueName, false, true, false, false, false, array("x-max-priority" => array("I", self::MAX_PRIORITY)));
            $this->producerChannel->queue_declare($queueName, false, true, false, false, false, array("x-max-priority" => array("I", self::MAX_PRIORITY)));

            $payload = array(
                "name" => $event->name,
                "args" => $args
            );
            $message = new AMQPMessage(json_encode($payload, JSON_UNESCAPED_UNICODE), array("content_type" => "application/json", "priority" => $event->getPriority()));

            $this->logger->debug("Publishing the '" . $event->name . "' event to RabbitMQ...", $payload);
            $this->producerChannel->basic_publish($message, "", $queueName);
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