<?php
    require_once(dirname(__FILE__) . "/../model/Subscription.php");

    class GetActiveSubscriptionsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;            

            return $databaseProvider
                ->statementBuilder("SELECT *, value * exchange_rate AS main_currency_value FROM expense_subscription WHERE expiration > UNIX_TIMESTAMP()")
                ->getMappedResultSet(function ($subscriptionRow) { 
                    return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                        $subscriptionRow["currency"], $subscriptionRow["main_currency_value"], $subscriptionRow["expiration"]); 
                });
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    } 
?>