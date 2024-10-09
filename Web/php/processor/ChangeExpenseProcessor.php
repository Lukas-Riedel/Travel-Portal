<?php
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/GetExchangeRateProcessor.php");
    require_once(dirname(__FILE__) . "/UpdateCurrenciesProcessor.php");

    class ChangeExpenseProcessor extends Processor { 
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            if (isset($input["description"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE expense SET description = ? WHERE id = ?")
                    ->withParameters($input["description"], $input["expenseId"])
                    ->execute();
            }

            if (isset($input["cost"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE expense SET value = ? WHERE id = ?")
                    ->withParameters($input["cost"], $input["expenseId"])
                    ->execute();
            }

            if (isset($input["currency"])) {            
                $exchangeRate = (new GetExchangeRateProcessor())
                    ->process(array(
                        "currency" => $input["currency"]));

                $databaseProvider
                    ->statementBuilder("UPDATE expense SET currency = ?, exchange_rate = ? WHERE id = ?")
                    ->withParameters($input["currency"], $exchangeRate, $input["expenseId"])
                    ->execute();
            }

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $input["tripId"]), NULL);
                    
            (new UpdateCurrenciesProcessor())
                ->process(NULL);

            $expenseRow = $databaseProvider
                ->statementBuilder("SELECT * FROM _expense_summary WHERE id = ?")
                ->withParameters($input["expenseId"])
                ->getSingleRow();
            
            return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"], $expenseRow["currency"], $expenseRow["main_currency_value"], $expenseRow["type"]);
        }

        public function getRequiredArguments() {
            return array("tripId", "expenseId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>