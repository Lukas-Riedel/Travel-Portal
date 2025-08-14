<?php
    define("PUBLIC_CONFIGURATION", "public");
    define("PRIVATE_CONFIGURATION", "private");

    class ConfigurationProvider {

        private $databaseProvider;

        public function __construct($databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function get($includePrivate) {          
            $entries = array();
            
            $levelEntries = $this->databaseProvider
                ->statementBuilder("SELECT * FROM configuration WHERE private <= ? ORDER BY `key` IS NOT NULL, type")
                ->withParameters($includePrivate ? 1 : 0)
                ->getResultSet();

            foreach ($levelEntries as &$entry) {
                $typeName = $this->convertTypeName($entry["type"]);
    
                if (!isset($entries[$typeName])) {
                    $entries[$typeName] = array();
                }
    
                if ($entry["key"] == NULL) {
                    $value = $this->cast($entry["value"]);
                    if (!in_array($value, $entries[$typeName])) {
                        $entries[$typeName][] = $value;
                    }
                }
                else {
                    $entries[$typeName][$entry["key"]] = $this->cast($entry["value"]);
                }
            }
    
            $result = array();
    
            foreach ($entries as $key => $values) {
                if (array_is_list($values) && count($values) == 1) {
                    $result[$key] = $values[0];
                }
                else {
                    $result[$key] = $values;
                }
            }

            return $result;
        }

        private function cast($value) {
            if (is_numeric($value)) {
                return is_nan($value) || is_infinite($value) ? $value : doubleval($value);
            }
            $convertedJson = json_decode($value, TRUE);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $convertedJson;
            }
            return $value;
        }

        private function convertTypeName($typeName) {
            $tokens = array_map("strtolower", explode("_", $typeName));
            return $tokens[0] . (count($tokens) > 1 ? implode("", array_map("ucfirst", array_slice($tokens, 1))) : "");
        }
    }
?>