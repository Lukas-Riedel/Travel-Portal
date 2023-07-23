<?php
    class ChangeConfigurationProcessor extends Processor {
        public function process($input) {
            global $databaseProvider;

            $key = isset($input["key"]) ? $input["key"] : NULL;

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE FIND_IN_SET('modifiable', levels) AND type = ? AND `key` " . $databaseProvider->getIsNullOrEqualTo($key))
                ->withParameters($input["value"], $input["type"])
                ->execute() > 0;

            if (!$wasUpdated) {
                throw new InvalidArgumentException("The configuration was not updated. Either it does not exist, or no changes were made.");
            }

            return $key == NULL 
                ? array($this->convertTypeName($input["type"]) => $input["value"])
                : array($this->convertTypeName($input["type"]) => array($key => $input["value"]));
        }

        public function getRequiredArguments() {
            return array("type", "value");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function convertTypeName($typeName) {
            $tokens = array_map("strtolower", explode("_", $typeName));
            return $tokens[0] . (count($tokens) > 1 ? implode("", array_map("ucfirst", array_slice($tokens, 1))) : "");
        }
    }
?>