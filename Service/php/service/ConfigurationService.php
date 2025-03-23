<?php
    class ConfigurationService {
        public function getConfigurationEntries($levels) : array {
            global $configurationProvider;

            if (in_array(PRIVATE_CONFIGURATION, $levels)) {
                throw new AuthorizationException("The user is not authorized to view private configuration.");
            }
            
            return $configurationProvider->get(...$levels);
        }

        public function getBaseUrl() : string {
            return BASE_URL;
        }        

        public function onFlightLogged(mixed $message) : void {
            $airlineCode = substr($message["flight"], 0, 2);
            $this->addConfigurationEntryIfNotExists("AIRLINES", array("public", "modifiable"), $airlineCode, $airlineCode);
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
        public function getConfigurationForTypeAndKey($type, $key) : ?string {
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
                ->statementBuilder("SELECT value FROM configuration WHERE NOT FIND_IN_SET('private', levels) AND type = ? AND `key` " . $databaseProvider->getIsNullOrEqualTo($key))
                ->withParameters($type)
                ->getSingleRow();

            if ($configurationEntryRow === NULL) {
                return NULL;
            }

            return $key == NULL 
                ? array($this->convertTypeName($type) => $configurationEntryRow["value"])
                : array($this->convertTypeName($type) => array($key => $configurationEntryRow["value"]));
        }

        public function addConfigurationEntryIfNotExists($type, $levels, $key, $value) : void {
            global $databaseProvider;

            if ($this->getConfigurationEntry($type, $key) !== NULL) {
                return;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO configuration (type, levels, `key`, value) VALUES (?, ?, ?, ?)")
                ->withParameters($type, implode(",", $levels), $key, $value)
                ->execute();
        }

        public function updateConfigurationEntryValue($type, $key, $value) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE FIND_IN_SET('modifiable', levels) AND type = ? AND `key` " . $databaseProvider->getIsNullOrEqualTo($key))
                ->withParameters($value, $type)
                ->execute() > 0;
        }

        public function updateConfigurationEntryVisibility($levels, $type, $key) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE configuration SET levels = ? WHERE type = ? AND `key` = ?")
                ->withParameters(implode(",", $levels), $type, $key)
                ->execute() === 1;
        }

        private function convertTypeName($typeName) {
            $tokens = array_map("strtolower", explode("_", $typeName));
            return $tokens[0] . (count($tokens) > 1 ? implode("", array_map("ucfirst", array_slice($tokens, 1))) : "");
        }
    }
?>