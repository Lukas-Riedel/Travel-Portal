<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");

    class GetExchangeRateProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration;
            
            if ($input["currency"] == $configuration["mainCurrency"]) {
                return 1;
            }

            $cachedRate = $databaseProvider
                ->statementBuilder("SELECT exchange_rate FROM cache_exchange_rate WHERE currency = ?")
                ->withParameters($input["currency"])
                ->getSingleColumn("exchange_rate");

            if ($cachedRate != NULL) {
                return $cachedRate;
            }    
    
            $apiResponse = (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "GET", 
                    "url" => "https://v6.exchangerate-api.com/v6/88f93f800acc098fbf682685/latest/" . $configuration["mainCurrency"]));
            
            if ($apiResponse != NULL && array_key_exists($input["currency"], $apiResponse["conversion_rates"])) {
                $rate = (1 / doubleval($apiResponse["conversion_rates"][$input["currency"]]));
                $databaseProvider
                    ->statementBuilder("INSERT INTO cache_exchange_rate (currency, exchange_rate, last_update) VALUES (?, ?, UNIX_TIMESTAMP())")
                    ->withParameters($input["currency"], $rate)
                    ->execute();
                return $rate;
            }
    
            return 0;
        }

        public function getRequiredArguments() {
            return array("currency");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>