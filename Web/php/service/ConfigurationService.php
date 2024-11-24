<?php
    class ConfigurationService {
        public function updateConfigurationVisibility($levels, $type, $key) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE configuration SET levels = ? WHERE type = ? AND `key` = ?")
                ->withParameters(implode(",", $levels), $type, $key)
                ->execute() === 1;
        }
    }
?>