<?php
    class UpdateCurrenciesProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration;

            // First, list recently used currencies.
            $newCurrencies = $databaseProvider
                ->statementBuilder("SELECT DISTINCT currency FROM expense ORDER BY id DESC LIMIT 5")
                ->getResultSetForColumn("currency");

            // Then, list frequently used currencies.
            $frequentlyUsedCurrencies = explode(",", $databaseProvider
                ->statementBuilder("SELECT GROUP_CONCAT(currency SEPARATOR ',') AS currencies FROM (SELECT currency FROM expense GROUP BY currency ORDER BY COUNT(*) DESC) t")
                ->getSingleColumn("currencies"));
    
            foreach ($frequentlyUsedCurrencies as &$currency) {
                if (!in_array($currency, $newCurrencies)) {
                    $newCurrencies[] = $currency;
                }
            }

            // Last, list all remaining currencies.
            foreach ($configuration["currencies"] as &$currency) {
                if (!in_array($currency, $newCurrencies)) {
                    $newCurrencies[] = $currency;
                }
            }

            $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'CURRENCIES'")
                ->withParameters(json_encode($newCurrencies))
                ->execute();

            return TRUE;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>