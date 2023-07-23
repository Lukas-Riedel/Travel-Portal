<?php
    require_once(dirname(__FILE__) . "/../model/Subscription.php");
    require_once(dirname(__FILE__) . "/GetExchangeRateProcessor.php");

    class AddSubscriptionProcessor extends Processor {  
        public function process($input) {
            global $databaseProvider;
                        
            $exchangeRate = (new GetExchangeRateProcessor())
                ->process(array(
                    "currency" => $input["currency"]));

            $databaseProvider
                ->statementBuilder("INSERT INTO expense_subscription (value, currency, exchange_rate, description, expiration) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($input["value"], $input["currency"], $exchangeRate, $input["description"], $input["expiration"])
                ->execute();
                
            $subscriptionRow = $databaseProvider
                ->statementBuilder("SELECT *, value * exchange_rate AS main_currency_value FROM expense_subscription ORDER BY id DESC LIMIT 1")
                ->getSingleRow();
                
            return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                $subscriptionRow["currency"], $subscriptionRow["main_currency_value"], $subscriptionRow["expiration"]);
        }

        public function getRequiredArguments() {
            return array("description", "value", "currency", "expiration");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>