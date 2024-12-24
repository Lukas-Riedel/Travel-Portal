<?php
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/../model/Subscription.php");
    require_once(dirname(__FILE__) . "/../processor/GetHttpResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/UpdateCurrenciesProcessor.php");

    class ExpenseService {
        public function getExpense($expenseId) : ?Expense {
            global $databaseProvider;

            $expenseRow = $databaseProvider
                ->statementBuilder("SELECT * FROM _expense_summary WHERE id = ?")
                ->withParameters($expenseId)
                ->getSingleRow();
            
            return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"], $expenseRow["currency"], $expenseRow["main_currency_value"], $expenseRow["type"]);
        }

        public function createExpense($tripId, $value, $currency, $type, $description, $subscriptionId) : Expense {            
            global $databaseProvider, $schedulingProvider;
                      
            $exchangeRate = $this->getExchangeRate($currency);

            $databaseProvider
                ->statementBuilder("INSERT INTO expense (trip_id, value, currency, exchange_rate, type, description, timestamp, subscription_id) VALUES (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), ?)")
                ->withParameters($tripId, $value, $currency, $exchangeRate, $type, $description, $subscriptionId)
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
                        
            $exchangeRate = $this->getExchangeRate($currency);

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

        public function getActiveSubscriptions() : array {
            global $databaseProvider;            

            return $databaseProvider
                ->statementBuilder("SELECT *, value * exchange_rate AS main_currency_value FROM expense_subscription WHERE expiration > UNIX_TIMESTAMP()")
                ->getMappedResultSet(function ($subscriptionRow) { 
                    return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                        $subscriptionRow["currency"], $subscriptionRow["main_currency_value"], $subscriptionRow["expiration"]); 
                });
        }
 
        public function updateExpenseDescription($expenseId, $description) : bool {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("UPDATE expense SET description = ? WHERE id = ?")
                ->withParameters($description, $expenseId)
                ->execute() === 1;
        }

        public function updateExpenseValue($expenseId, $value, $tripId) : bool {
            global $databaseProvider, $schedulingProvider;
            
            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE expense SET value = ? WHERE id = ?")
                ->withParameters($value, $expenseId)
                ->execute() === 1;
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $tripId), NULL);

            return $wasUpdated;
        }

        public function updateExpenseCurrency($expenseId, $currency, $tripId) : bool {
            global $databaseProvider, $schedulingProvider;

            $exchangeRate = $this->getExchangeRate($currency);

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE expense SET currency = ?, exchange_rate = ? WHERE id = ?")
                ->withParameters($currency, $exchangeRate, $expenseId)
                ->execute() === 1;
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $tripId), NULL);

            return $wasUpdated;
        }

        public function removeExpense($expenseId) : bool {
            global $databaseProvider, $schedulingProvider;

            $tripId = $databaseProvider
                ->statementBuilder("SELECT trip_id FROM expense WHERE id = ?")
                ->withParameters($expenseId)
                ->getSingleColumn("trip_id");

            $wasDeleted = $databaseProvider
                ->statementBuilder("DELETE FROM expense WHERE id = ?")
                ->withParameters($expenseId)
                ->execute() === 1;
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $tripId), NULL);
                    
            (new UpdateCurrenciesProcessor())
                ->process(NULL);  

            return $wasDeleted;
        }

        public function getExchangeRate($currency) : float {      
                global $databaseProvider, $configuration;
                
                if ($currency === $configuration["mainCurrency"]) {
                    return 1;
                }
    
                $cachedRate = $databaseProvider
                    ->statementBuilder("SELECT exchange_rate FROM cache_exchange_rate WHERE currency = ?")
                    ->withParameters($currency)
                    ->getSingleColumn("exchange_rate");
    
                if ($cachedRate != NULL) {
                    return $cachedRate;
                }    
        
                $apiResponse = (new GetHttpResponseProcessor())
                    ->process(array(
                        "method" => "GET", 
                        "url" => "https://v6.exchangerate-api.com/v6/88f93f800acc098fbf682685/latest/" . $configuration["mainCurrency"]));
                
                if ($apiResponse === NULL || !array_key_exists($currency, $apiResponse["conversion_rates"])) {
                    return 0;
                }
        
                $rate = (1 / doubleval($apiResponse["conversion_rates"][$currency]));

                $databaseProvider
                    ->statementBuilder("INSERT INTO cache_exchange_rate (currency, exchange_rate, last_update) VALUES (?, ?, UNIX_TIMESTAMP())")
                    ->withParameters($currency, $rate)
                    ->execute();

                return $rate;
        }
    }
?>