<?php
    class ConfigurationService {
        public function updateConfigurationVisibility($levels, $type, $key) : void {
            global $databaseProvider;

            $databaseProvider
                ->statementBuilder("UPDATE configuration SET levels = ? WHERE type = ? AND `key` = ?")
                ->withParameters(implode(",", $levels), $type, $key)
                ->execute();
        }
    }
?>