<?php
    class ConfigurationService {
        public function getConfigurationEntries($levels) : array {
            global $configurationProvider;

            if (in_array(PRIVATE_CONFIGURATION, $levels)) {
                throw new AuthorizationException("The user is not authorized to view private configuration.");
            }
            
            return $configurationProvider->get(...$levels);
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