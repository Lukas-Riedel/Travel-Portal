<?php
    namespace Service\Client;

    use Monolog\Logger;
    use PhpAmqpLib\Channel\AMQPChannel;
    use PhpAmqpLib\Connection\AMQPSSLConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    class MessagingClient {

        private const MAX_PRIORITY = 5;

        private ?AMQPSSLConnection $connection = NULL;
        private ?AMQPChannel $channel = NULL;

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
        }

        public function __destruct() {
            if ($this->channel !== NULL) {
                $this->channel->close();
            }
            if ($this->connection !== NULL) {
                $this->connection->close();
            }
        }

        public function publishEvent(\Event $event, ?array $args, string $queueName) : void {
            $this->init();
            $this->channel->queue_declare($queueName, FALSE, TRUE, FALSE, FALSE, FALSE, array("x-max-priority" => array("I", self::MAX_PRIORITY)));

            $payload = array(
                "event" => $event->name,
                "args" => $args
            );
            $message = new AMQPMessage(json_encode($payload), array("content_type" => "application/json", "priority" => $event->getPriority()));

            $this->logger->debug("Publishing the '" . $event->name . "' event to RabbitMQ...", $payload);
            $this->channel->basic_publish($message, "", $queueName);
        }

        public function getChannel() : AMQPChannel {
            $this->init();
            return $this->channel;
        }

        private function init() {
            if ($this->connection === NULL || $this->channel === NULL) {                    
                $this->connection = new AMQPSSLConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PW, RMQ_VHOST,
                    array("verify_peer" => TRUE, "verify_peer_name" => TRUE));
                $this->channel = $this->connection->channel();
                $this->channel->basic_qos(NULL, 1, NULL);
            }
        }
    }
?>