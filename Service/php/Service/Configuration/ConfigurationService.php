<?php
    namespace Service\Service\Configuration;

    class ConfigurationService {

        private readonly ConfigurationMapper $configurationMapper;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->configurationMapper = new ConfigurationMapper($databaseProvider);
        }
        
        public function getAllConfigurationEntries(bool $allowPrivate) : mixed {
            return $this->configurationMapper->selectAllConfigurationEntries($allowPrivate);
        }
        
        public function getConfigurationEntry(string $key) : mixed {
            return $this->configurationMapper->selectConfigurationEntry($key);
        }

        public function updateConfigurationEntry(string $key, mixed $value) : bool {
            return $this->configurationMapper->updateConfigurationEntry($key, $value);
        }
    }
?>