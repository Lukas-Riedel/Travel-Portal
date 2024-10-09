<?php
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/GetExchangeRateProcessor.php");
    require_once(dirname(__FILE__) . "/UpdateCurrenciesProcessor.php");
    
    class AddExpenseProcessor extends Processor {    
        public function process($input) {
            global $databaseProvider, $schedulingProvider;
            
            $timestamp = isset($input["timestamp"]) ? $input["timestamp"] : time();
            $subscriptionId = isset($input["subscriptionId"]) ? $input["subscriptionId"] : NULL;
            
            $exchangeRate = (new GetExchangeRateProcessor())
                ->process(array(
                    "currency" => $input["currency"]));

            $databaseProvider
                ->statementBuilder("INSERT INTO expense (trip_id, `value`, currency, exchange_rate, `type`, `description`, `timestamp`, subscription_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->withParameters($input["tripId"], $input["cost"], $input["currency"], $exchangeRate, $input["type"], $input["description"], $timestamp, $subscriptionId)
                ->execute();   

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $input["tripId"]), NULL);

            (new UpdateCurrenciesProcessor())
                ->process(NULL);

            $expenseRow = $databaseProvider
                ->statementBuilder("SELECT * FROM _expense_summary ORDER BY id DESC LIMIT 1")
                ->getSingleRow();
            
            return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"], $expenseRow["currency"], $expenseRow["main_currency_value"], $expenseRow["type"]);
        }

        public function getRequiredArguments() {
            return array("tripId", "type", "description", "cost", "currency");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>