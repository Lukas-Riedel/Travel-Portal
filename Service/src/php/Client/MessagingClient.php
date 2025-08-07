<?php
    namespace Service\Client;

    use Monolog\Logger;
    use PhpAmqpLib\Channel\AMQPChannel;
    use PhpAmqpLib\Connection\AMQPSSLConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    class MessagingClient {

        private const MAX_PRIORITY = 5;

        private ?AMQPSSLConnection $connection = NULL;
        private ?AMQPChannel $producerChannel = NULL;
        private ?AMQPChannel $consumerChannel = NULL;

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
        }

        public function __destruct() {
            if ($this->producerChannel !== NULL) {
                $this->producerChannel->close();
            }
            if ($this->consumerChannel !== NULL) {
                $this->consumerChannel->close();
            }
            if ($this->connection !== NULL) {
                $this->connection->close();
            }
        }

        public function publishEvent(\Event $event, ?array $args, string $queueName) : void {
            $this->init();
            $this->consumerChannel->queue_declare($queueName, FALSE, TRUE, FALSE, FALSE, FALSE, array("x-max-priority" => array("I", self::MAX_PRIORITY)));
            $this->producerChannel->queue_declare($queueName, FALSE, TRUE, FALSE, FALSE, FALSE, array("x-max-priority" => array("I", self::MAX_PRIORITY)));

            $payload = array(
                "event" => $event->name,
                "args" => $args
            );
            $message = new AMQPMessage(json_encode($payload), array("content_type" => "application/json", "priority" => $event->getPriority()));

            $this->logger->debug("Publishing the '" . $event->name . "' event to RabbitMQ...", $payload);
            $this->producerChannel->basic_publish($message, "", $queueName);
        }

        public function getConsumerChannel() : AMQPChannel {
            $this->init();
            return $this->consumerChannel;
        }

        private function init() {
            if ($this->connection === NULL || $this->producerChannel === NULL) {                    
                $this->connection = new AMQPSSLConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PW, RMQ_VHOST,
                    array("verify_peer" => TRUE, "verify_peer_name" => TRUE));
                $this->producerChannel = $this->connection->channel();
                $this->consumerChannel = $this->connection->channel();
                $this->consumerChannel->basic_qos(NULL, 1, NULL);
            }
        }
    }
?>