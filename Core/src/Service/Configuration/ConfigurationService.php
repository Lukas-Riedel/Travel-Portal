<?php
    namespace Core\Service\Configuration;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    // TODO: Initialize the database with default values if they don't exist -> in deploy.php.
    class ConfigurationService {

        private readonly ConfigurationMapper $configurationMapper;

        private readonly EventPublisher $eventPublisher;

        private readonly TransactionManager $transactionManager;

        private readonly string $rabbitMqHost;
        private readonly string $rabbitMqPort;
        private readonly string $rabbitMqVhost;
        private readonly string $rabbitMqUser;
        private readonly string $rabbitMqPassword;

        public function __construct(DatabaseClient $databaseClient, EventPublisher $eventPublisher,
            string $rabbitMqHost, string $rabbitMqPort, string $rabbitMqVhost, string $rabbitMqUser, string $rabbitMqPassword) {
            $this->configurationMapper = new ConfigurationMapper($databaseClient);
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
            $this->rabbitMqHost = $rabbitMqHost;
            $this->rabbitMqPort = $rabbitMqPort;
            $this->rabbitMqVhost = $rabbitMqVhost;
            $this->rabbitMqUser = $rabbitMqUser;
            $this->rabbitMqPassword = $rabbitMqPassword;
        }
        
        public function getAllConfigurationEntries(bool $allowPrivate) : mixed {
            return $this->configurationMapper->selectAllConfigurationEntries($allowPrivate);
        }
        
        public function getConfigurationEntry(string $key) : mixed {
            return $this->configurationMapper->selectConfigurationEntry($key);
        }

        public function getAgentConfigurationEntries() : mixed {
            return array(
                "spring.rabbitmq.host" => $this->rabbitMqHost,
                "spring.rabbitmq.port" => $this->rabbitMqPort,
                "spring.rabbitmq.virtual-host" => $this->rabbitMqVhost,
                "spring.rabbitmq.username" => $this->rabbitMqUser,
                "spring.rabbitmq.password" => $this->rabbitMqPassword
            );
        }

        public function updateConfigurationEntry(string $key, mixed $value) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$wasUpdated, &$key, &$value) {
                $wasUpdated &= $this->configurationMapper->updateConfigurationEntry($key, $value);
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::ConfigurationEntryUpdated($key));
                }                
            });
            return $wasUpdated;
        }
    }
?>