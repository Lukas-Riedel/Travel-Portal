<?php
    namespace Core\Service\Configuration;
    
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class ConfigurationService {

        private readonly ConfigurationMapper $configurationMapper;

        private readonly EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, EventPublisher $eventPublisher) {
            $this->configurationMapper = new ConfigurationMapper($databaseProvider);
            $this->eventPublisher = $eventPublisher;
        }
        
        public function getAllConfigurationEntries(bool $allowPrivate) : mixed {
            return $this->configurationMapper->selectAllConfigurationEntries($allowPrivate);
        }
        
        public function getConfigurationEntry(string $key) : mixed {
            return $this->configurationMapper->selectConfigurationEntry($key);
        }

        public function updateConfigurationEntry(string $key, mixed $value) : bool {
            $wasUpdated = $this->configurationMapper->updateConfigurationEntry($key, $value);
            if ($wasUpdated) {
                $this->eventPublisher->publish(Event::ConfigurationEntryUpdated($key));
            }
            return $wasUpdated;
        }
    }
?>