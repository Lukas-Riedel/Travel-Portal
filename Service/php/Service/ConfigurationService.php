<?php
    class ConfigurationService {
        public function getConfigurationEntries($includePrivate) : array {
            global $configurationProvider;            
            return $configurationProvider->get($includePrivate);
        }    

        // TODO
        public function getConfigurationForType($type) : ?string {
            global $configuration;

            return $configuration[$type];
        }

        public function getConfigurationKeysForType($type) : array {
            global $configuration;

            return array_keys($configuration[$type]);
        }

        public function getConfigurationValuesForType($type) : array {
            global $configuration;

            return array_values($configuration[$type]);
        }

        // TODO
        public function getConfigurationForTypeAndKey($type, $key) : mixed {
            global $configuration;

            return $configuration[$type][$key];
        }

        // TODO
        public function getConfigurationKeyForTypeAndValue($type, $value) : ?string {
            global $configuration, $databaseProvider;

            foreach ($this->getConfigurationKeysForType($type) as $key) {
                if ($configuration[$type][$key] === $value) {
                    return $key;
                }
            }

            return NULL;
        }

        public function existsForTypeAndKey($type, $key) : bool {
            global $configuration;

            return array_key_exists($key, $configuration[$type]);
        }

        public function getConfigurationEntry($type, $key) : ?array {
            global $databaseProvider;

            $configurationEntryRow = $databaseProvider
                ->statementBuilder("SELECT value FROM configuration WHERE type = ? AND `key` " . $databaseProvider->getIsNullOrEqualTo($key))
                ->withParameters($type)
                ->getSingleRow();

            if ($configurationEntryRow === NULL) {
                return NULL;
            }

            return $key == NULL 
                ? array($this->convertTypeName($type) => $configurationEntryRow["value"])
                : array($this->convertTypeName($type) => array($key => $configurationEntryRow["value"]));
        }

        public function updateConfigurationEntryValue($type, $key, $value) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE type = ? AND `key` " . $databaseProvider->getIsNullOrEqualTo($key))
                ->withParameters($value, $type)
                ->execute() > 0;
        }

        private function convertTypeName($typeName) {
            $tokens = array_map("strtolower", explode("_", $typeName));
            return $tokens[0] . (count($tokens) > 1 ? implode("", array_map("ucfirst", array_slice($tokens, 1))) : "");
        }
    }
?>