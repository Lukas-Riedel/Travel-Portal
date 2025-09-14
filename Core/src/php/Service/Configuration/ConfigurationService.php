<?php
    namespace Core\Service\Configuration;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class ConfigurationService {

        private readonly ConfigurationMapper $configurationMapper;

        private readonly EventPublisher $eventPublisher;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, EventPublisher $eventPublisher) {
            $this->configurationMapper = new ConfigurationMapper($databaseClient);
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }
        
        public function getAllConfigurationEntries(bool $allowPrivate) : mixed {
            return $this->configurationMapper->selectAllConfigurationEntries($allowPrivate);
        }
        
        public function getConfigurationEntry(string $key) : mixed {
            return $this->configurationMapper->selectConfigurationEntry($key);
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