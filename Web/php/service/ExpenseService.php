<?php
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/../model/Subscription.php");
    require_once(dirname(__FILE__) . "/../processor/GetExchangeRateProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/UpdateCurrenciesProcessor.php");

    class ExpenseService {
        public function createExpense($tripId, $cost, $currency, $type, $description, $subscriptionId) : Expense {            
            global $databaseProvider, $schedulingProvider;
                      
            $exchangeRate = (new GetExchangeRateProcessor())
                ->process(array(
                    "currency" => $currency));

            $databaseProvider
                ->statementBuilder("INSERT INTO expense (trip_id, value, currency, exchange_rate, type, description, timestamp, subscription_id) VALUES (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), ?)")
                ->withParameters($tripId, $cost, $currency, $exchangeRate, $type, $description, $subscriptionId)
                ->execute();   

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $tripId), NULL);

            (new UpdateCurrenciesProcessor())
                ->process(NULL);

            $expenseRow = $databaseProvider
                ->statementBuilder("SELECT * FROM _expense_summary ORDER BY id DESC LIMIT 1")
                ->getSingleRow();
            
            return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"], $expenseRow["currency"], $expenseRow["main_currency_value"], $expenseRow["type"]);
        }

        public function createSubscription($value, $currency, $description, $expiration) : Subscription {
            global $databaseProvider;
                        
            $exchangeRate = (new GetExchangeRateProcessor())
                ->process(array(
                    "currency" => $currency));

            $databaseProvider
                ->statementBuilder("INSERT INTO expense_subscription (value, currency, exchange_rate, description, expiration) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($value, $currency, $exchangeRate, $description, $expiration)
                ->execute();
                
            $subscriptionRow = $databaseProvider
                ->statementBuilder("SELECT *, value * exchange_rate AS main_currency_value FROM expense_subscription ORDER BY id DESC LIMIT 1")
                ->getSingleRow();
                
            return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                $subscriptionRow["currency"], $subscriptionRow["main_currency_value"], $subscriptionRow["expiration"]);
        }
    }
?>