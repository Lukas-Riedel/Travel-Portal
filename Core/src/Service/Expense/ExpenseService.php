<?php
    namespace Core\Service\Expense;

    use Common\Client\Encryption\EncryptionClient;
    use Common\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\ExchangeRate\ExchangeRateClient;

    class ExpenseService {
        
        private const EXCHANGE_RATE_CACHE_KEY = "ExpenseService:ExchangeRates";
        private const EXCHANGE_RATE_CACHE_TTL = CommonConstants::ONE_DAY_SECONDS;

        private readonly ExpenseMapper $expenseMapper;        
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly ExchangeRateClient $exchangeRateClient;
        private readonly CacheClient $distributedCacheClient;
        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService,
            EventPublisher $eventPublisher, ExchangeRateClient $exchangeRateClient, CacheClient $distributedCacheClient,
            EncryptionClient $encryptionClient) {
            $this->expenseMapper = new ExpenseMapper($databaseClient, $encryptionClient);
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->exchangeRateClient = $exchangeRateClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->transactionManager = $databaseClient;
        }

        public function createExpense(string $tripId, float $value, ExpenseCurrency $currency, ExpenseType $type, string $description, ?string $subscriptionId) : Expense {                      
            $exchangeRate = $this->getExchangeRate($currency);

            // TODO: This is inacurrate, the subscription share is not included in the main currency value.
            $expense = new Expense(null, $description, $value, $currency, $exchangeRate, $type, $exchangeRate * $value, 
                $subscriptionId === null ? null : $this->expenseMapper->selectSubscription($subscriptionId));
            $this->transactionManager->executeAtomically(function() use(&$expense, &$tripId, &$subscriptionId) {
                $this->expenseMapper->insertExpense($expense, $tripId, $subscriptionId);

                $this->eventPublisher->publish(Event::ExpenseCreated($expense->getId(), $tripId));
            });

            return $expense;
        }

        public function createVoucher(string $code, string $issuer, float $value, ExpenseCurrency $currency, ?int $expiration) : Voucher {
            $voucher = new Voucher(null, $code, $issuer, $value, $currency, $expiration);
            $this->expenseMapper->insertVoucher($voucher);

            return $voucher;
        }

        public function createSubscription(float $value, ExpenseCurrency $currency, string $description, int $expiration) : Subscription {                        
            $exchangeRate = $this->getExchangeRate($currency);

            $subscription = new Subscription(null, $description, $value, $currency, $exchangeRate, $expiration, 0);
            $this->expenseMapper->insertSubscription($subscription);

            return $subscription;
        }

        public function getAllVouchers() : array {
            $this->expenseMapper->deleteExpiredVouchers();
            return $this->expenseMapper->selectAllVouchers();
        }

        public function getVoucher(string $voucherId) : ?Voucher {
            $this->expenseMapper->deleteExpiredVouchers();
            return $this->expenseMapper->selectVoucher($voucherId);
        }

        public function getExpensesForTrip(string $tripId) : array {
            return $this->expenseMapper->selectExpensesForTrip($tripId);
        }

        public function getExpense(string $expenseId) : ?Expense {
            return $this->expenseMapper->selectExpense($expenseId);
        }

        public function getActiveSubscription(string $subscriptionId) : ?Subscription {
            $subscription = $this->expenseMapper->selectSubscription($subscriptionId);
            return $subscription === null || $subscription->isExpired() ? null : $subscription;
        }

        public function getActiveSubscriptions() : array {
            return $this->expenseMapper->selectActiveSubscriptions();
        }
 
        public function updateExpenseDescription(string $expenseId, string $description, string $tripId) : bool {  
            $wasUpdated = true; 
            $this->transactionManager->executeAtomically(function() use(&$wasUpdated, &$expenseId, &$description, &$tripId) {
                $wasUpdated &= $this->expenseMapper->updateExpenseDescription($expenseId, $description);
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::ExpenseUpdated($expenseId, $tripId));
                }
            });
            return $wasUpdated;
        }

        public function updateVoucherValue(string $voucherId, float $value) : bool {
            if ($value <= 0) {
                throw new \InvalidArgumentException("Unable to update the voucher value to $value.");
            }
            return $this->expenseMapper->updateVoucherValue($voucherId, $value);
        }

        public function updateExpenseValue(string $expenseId, float $value, string $tripId) : bool {   
            $wasUpdated = true; 
            $this->transactionManager->executeAtomically(function() use(&$wasUpdated, &$expenseId, &$value, &$tripId) {
                $wasUpdated &= $this->expenseMapper->updateExpenseValue($expenseId, $value);
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::ExpenseUpdated($expenseId, $tripId));
                }
            });
            return $wasUpdated;
        }

        public function updateExpenseCurrency(string $expenseId, ExpenseCurrency $currency, string $tripId) : bool {
            $exchangeRate = $this->getExchangeRate($currency);

            $wasUpdated = true; 
            $this->transactionManager->executeAtomically(function() use(&$wasUpdated, &$expenseId, &$currency, &$exchangeRate, &$tripId) {
                $wasUpdated &= $this->expenseMapper->updateExpenseCurrency($expenseId, $currency, $exchangeRate);
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::ExpenseUpdated($expenseId, $tripId));
                }
            });
            return $wasUpdated;
        }

        public function removeExpense(string $expenseId, string $tripId) : bool {
            $wasRemoved = true;
            $this->transactionManager->executeAtomically(function() use(&$wasRemoved, &$expenseId, &$tripId) {
                $wasRemoved &= $this->expenseMapper->deleteExpense($expenseId) > 0;
                if ($wasRemoved) {
                    $this->eventPublisher->publish(Event::ExpenseRemoved($expenseId, $tripId));
                }
            });
            return $wasRemoved;
        }

        public function removeVoucher(string $voucherId) : bool {
            return $this->expenseMapper->deleteVoucher($voucherId) > 0;
        }

        public function removeActiveSubscription(string $subscriptionId) : bool {
            return $this->expenseMapper->deleteActiveSubscription($subscriptionId) > 0;
        }

        private function getExchangeRate(ExpenseCurrency $currency) : float {       
            $mainCurrency = $this->configurationService->getConfigurationEntry("expensify")["mainCurrency"];
            if ($currency->value === $mainCurrency) {
                return 1;
            }

            $cachedExchangeRates = $this->distributedCacheClient->get(self::EXCHANGE_RATE_CACHE_KEY);
            if ($cachedExchangeRates !== null) {
                return $cachedExchangeRates[$currency->value] ?? 0;
            }        

            $exchangeRates = $this->exchangeRateClient->getExchangeRates($mainCurrency);
            $this->distributedCacheClient->set(self::EXCHANGE_RATE_CACHE_KEY, $exchangeRates, self::EXCHANGE_RATE_CACHE_TTL);

            return $exchangeRates[$currency->value] ?? 0;
        }
    }
?>