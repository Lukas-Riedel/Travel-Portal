<?php
    namespace Service\Service\Expense;

    class ExpenseMapper {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectExpensesForTrip(string $tripId) : array { 
            $sql = <<<'SQL'
                SELECT *
                FROM expense_summary
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($expenseRow) {
                    return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"],
                        $expenseRow["currency"], $expenseRow["exchange_rate"], ExpenseType::from($expenseRow["type"]), $expenseRow["main_currency_value"]);
                });
        }

        public function selectExpense(string $expenseId) : ?Expense {
            $sql = <<<'SQL'
                SELECT *
                FROM expense_summary
                WHERE id = ?
            SQL;

            $expenseRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($expenseId)
                ->getSingleRow();

            return $expenseRow === NULL ? NULL : new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"],
                $expenseRow["currency"], $expenseRow["exchange_rate"], ExpenseType::from($expenseRow["type"]), $expenseRow["main_currency_value"]);
        }

        public function selectAllActiveSubscriptions() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM expense_subscription
                WHERE expiration > UNIX_TIMESTAMP()
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($subscriptionRow) {
                    return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                        $subscriptionRow["currency"], $subscriptionRow["exchange_rate"], $subscriptionRow["expiration"]);
                });
        }

        public function selectExchangeRate(string $currency) : ?float {
            $sql = <<<'SQL'
                SELECT exchange_rate
                FROM cache_exchange_rate
                WHERE currency = ?
                    AND expiration > UNIX_TIMESTAMP()
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($currency)
                ->getSingleColumn("exchange_rate");
        }

        public function insertExchangeRate(string $currency, float $exchangeRate, int $validity) : bool {
            $sql = <<<'SQL'
                INSERT INTO cache_exchange_rate (
                    currency,
                    exchange_rate,
                    expiration
                )
                VALUES (
                    ?,
                    ?,
                    UNIX_TIMESTAMP() + ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($currency, $exchangeRate, $validity)
                ->execute() === 1;
        }

        public function insertExpense(Expense $expense, string $tripId, ?string $subscriptionId) : bool {
            $sql = <<<'SQL'
                INSERT INTO expense (
                    trip_id,
                    value,
                    currency,
                    exchange_rate,
                    type,
                    description,
                    timestamp,
                    subscription_id
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    UNIX_TIMESTAMP(),
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId, $expense->getValue(), $expense->getCurrency(), $expense->getExchangeRate(), $expense->getType()->value,
                    $expense->getDescription(), $subscriptionId)
                ->execute() === 1;                

            if ($wasInserted) {
                $expense->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function insertSubscription(Subscription $subscription) : bool {
            $sql = <<<'SQL'
                INSERT INTO expense_subscription (
                    value,
                    currency,
                    exchange_rate,
                    description,
                    expiration
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($subscription->getValue(), $subscription->getCurrency(), $subscription->getExchangeRate(),
                    $subscription->getDescription(), $subscription->getExpiration())
                ->execute() === 1;                

            if ($wasInserted) {
                $subscription->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function updateExpenseDescription(string $expenseId, string $description) : bool {
            $sql = <<<'SQL'
                UPDATE expense
                SET description = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($description, $expenseId)
                ->execute() === 1;
        }

        public function updateExpenseValue(string $expenseId, float $value) : bool {
            $sql = <<<'SQL'
                UPDATE expense
                SET value = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($value, $expenseId)
                ->execute() === 1;
        }

        public function updateExpenseCurrency(string $expenseId, string $currency, float $exchangeRate) : bool {
            $sql = <<<'SQL'
                UPDATE expense
                SET currency = ?,
                    exchange_rate = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($currency, $exchangeRate, $expenseId)
                ->execute() === 1;
        }

        public function deleteExpense(string $expenseId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM expense
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($expenseId)
                ->execute();
        }
    }
?>