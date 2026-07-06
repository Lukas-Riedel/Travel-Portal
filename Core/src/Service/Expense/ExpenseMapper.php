<?php
    namespace Core\Service\Expense;

    use Common\Client\Encryption\EncryptionClient;
    use Core\Client\Database\DatabaseClient;

    class ExpenseMapper {

        private readonly DatabaseClient $databaseClient;
        private readonly EncryptionClient $encryptionClient;

        public function __construct(DatabaseClient $databaseClient, EncryptionClient $encryptionClient) {
            $this->databaseClient = $databaseClient;
            $this->encryptionClient = $encryptionClient;
        }

        public function selectAllVouchers() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM expense_voucher
                ORDER BY issuer,
                    expiration
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($voucherRow) {
                    return new Voucher($voucherRow["id"], $this->encryptionClient->decrypt($voucherRow["code"]), $voucherRow["issuer"],
                        $voucherRow["value"], ExpenseCurrency::from($voucherRow["currency"]), $voucherRow["expiration"]);
                });
        }

        public function selectVoucher(string $voucherId) : ?Voucher {
            $sql = <<<'SQL'
                SELECT *
                FROM expense_voucher
                WHERE id = ?
            SQL;

            $voucherRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($voucherId)
                ->getSingleRow();

            if ($voucherRow === null) {
                return null;
            }

            return new Voucher($voucherRow["id"], $this->encryptionClient->decrypt($voucherRow["code"]), $voucherRow["issuer"],
                $voucherRow["value"], ExpenseCurrency::from($voucherRow["currency"]), $voucherRow["expiration"]);
        }

        public function selectExpensesForTrip(string $tripId) : array { 
            $sql = <<<'SQL'
                SELECT *
                FROM expense
                WHERE trip_id = ?
                ORDER BY timestamp
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($expenseRow) {
                    $mainCurrencyValue = $expenseRow["exchange_rate"] * $expenseRow["value"];
                    if ($expenseRow["subscription_id"] !== null) {
                        $mainCurrencyValue += $this->selectSubscriptionMainCurrencyValue($expenseRow["subscription_id"]) / $this->selectSubscriptionOccurrencesCount($expenseRow["subscription_id"]);
                    }
                    
                    return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"], ExpenseCurrency::from($expenseRow["currency"]),
                        $expenseRow["exchange_rate"], ExpenseType::from($expenseRow["type"]), $mainCurrencyValue,
                        $expenseRow["subscription_id"] === null ? null : $this->selectSubscription($expenseRow["subscription_id"]));
                });
        }

        public function selectExpense(string $expenseId) : ?Expense {
            $sql = <<<'SQL'
                SELECT *
                FROM expense
                WHERE id = ?
            SQL;

            $expenseRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($expenseId)
                ->getSingleRow();

            if ($expenseRow === null) {
                return null;
            }

            $mainCurrencyValue = $expenseRow["exchange_rate"] * $expenseRow["value"];
            if ($expenseRow["subscription_id"] !== null) {
                $mainCurrencyValue += $this->selectSubscriptionMainCurrencyValue($expenseRow["subscription_id"]) / $this->selectSubscriptionOccurrencesCount($expenseRow["subscription_id"]);
            }

            return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"],
                ExpenseCurrency::from($expenseRow["currency"]), $expenseRow["exchange_rate"], ExpenseType::from($expenseRow["type"]),
                $mainCurrencyValue, $expenseRow["subscription_id"] === null ? null : $this->selectSubscription($expenseRow["subscription_id"]));
        }

        public function selectSubscription(string $subscriptionId) : ?Subscription {
            $sql = <<<'SQL'
                SELECT *,
                    (
                        SELECT COUNT(*) 
                        FROM expense 
                        WHERE subscription_id = expense_subscription.id
                    ) AS occurrences
                FROM expense_subscription
                WHERE id = ?
            SQL;

            $subscriptionRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($subscriptionId)
                ->getSingleRow();

            if ($subscriptionRow === null) {
                return null;
            }

            return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                    ExpenseCurrency::from($subscriptionRow["currency"]), $subscriptionRow["exchange_rate"],
                    $subscriptionRow["expiration"], $subscriptionRow["occurrences"]);
        }

        public function selectActiveSubscriptions() : array {
            $sql = <<<'SQL'
                SELECT *,
                    (
                        SELECT COUNT(*) 
                        FROM expense 
                        WHERE subscription_id = expense_subscription.id
                    ) AS occurrences
                FROM expense_subscription
                WHERE expiration > ROUND(EXTRACT(EPOCH FROM NOW()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($subscriptionRow) {
                    return new Subscription($subscriptionRow["id"], $subscriptionRow["description"], $subscriptionRow["value"],
                        ExpenseCurrency::from($subscriptionRow["currency"]), $subscriptionRow["exchange_rate"],
                        $subscriptionRow["expiration"], $subscriptionRow["occurrences"]);
                });
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
                    ROUND(EXTRACT(EPOCH FROM NOW())),
                    ?
                )
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId, $expense->getValue(), $expense->getCurrency()->value, $expense->getExchangeRate(), $expense->getType()->value,
                    $expense->getDescription(), $subscriptionId)
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $expense->setId($id);
            return true;
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
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($subscription->getValue(), $subscription->getCurrency()->value, $subscription->getExchangeRate(),
                    $subscription->getDescription(), $subscription->getExpiration())
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $subscription->setId($id);
            return true;
        }

        public function insertVoucher(Voucher $voucher) : bool {
            $sql = <<<'SQL'
                INSERT INTO expense_voucher (
                    code,
                    issuer,
                    value,
                    currency,
                    expiration
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($this->encryptionClient->encrypt($voucher->getCode()), $voucher->getIssuer(),
                    $voucher->getValue(), $voucher->getCurrency()->value, $voucher->getExpiration())
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $voucher->setId($id);
            return true;
        }

        public function updateExpenseDescription(string $expenseId, string $description) : bool {
            $sql = <<<'SQL'
                UPDATE expense
                SET description = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($value, $expenseId)
                ->execute() === 1;
        }

        public function updateExpenseCurrency(string $expenseId, ExpenseCurrency $currency, float $exchangeRate) : bool {
            $sql = <<<'SQL'
                UPDATE expense
                SET currency = ?,
                    exchange_rate = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($currency->value, $exchangeRate, $expenseId)
                ->execute() === 1;
        }

        public function updateVoucherValue(string $voucherId, float $value) : bool {
            $sql = <<<'SQL'
                UPDATE expense_voucher
                SET value = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($value, $voucherId)
                ->execute() === 1;
        }

        public function deleteExpense(string $expenseId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM expense
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($expenseId)
                ->execute();
        }

        public function deleteActiveSubscription(string $subscriptionId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM expense_subscription
                WHERE id = ?
                    AND expiration > ROUND(EXTRACT(EPOCH FROM NOW()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($subscriptionId)
                ->execute();
        }

        public function deleteVoucher(string $voucherId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM expense_voucher
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($voucherId)
                ->execute();
        }

        public function deleteExpiredVouchers() : int {
            $sql = <<<'SQL'
                DELETE
                FROM expense_voucher
                WHERE expiration IS NOT NULL
                    AND expiration < ROUND(EXTRACT(EPOCH FROM NOW()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        private function selectSubscriptionMainCurrencyValue(string $subscriptionId) : ?float {
            $sql = <<<'SQL'
                SELECT (value * exchange_rate) AS main_currency_value
                FROM expense_subscription
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($subscriptionId)
                ->getSingleColumn("main_currency_value");
        }

        private function selectSubscriptionOccurrencesCount(string $subscriptionId) : ?int {
            $sql = <<<'SQL'
                SELECT COUNT(*) AS occurrences
                FROM expense
                WHERE subscription_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($subscriptionId)
                ->getSingleColumn("occurrences");
        }
    }
?>