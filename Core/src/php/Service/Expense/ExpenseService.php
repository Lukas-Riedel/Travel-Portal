<?php
    namespace Core\Service\Expense;

    use Core\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;

    class ExpenseService {

        private const GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT = "https://v6.exchangerate-api.com/v6/%s/latest/%s";
        
        private const EXCHANGE_RATE_CACHE_KEY = "ExpenseService:ExchangeRates";
        private const EXCHANGE_RATE_CACHE_TTL = CommonConstants::ONE_DAY_SECONDS;

        private readonly ExpenseMapper $expenseMapper;
        
        private readonly HttpClient $httpClient;
        
        private readonly ConfigurationService $configurationService;

        private readonly EventPublisher $eventPublisher;

        private readonly CacheClient $cacheClient;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, HttpClient $httpClient,
            ConfigurationService $configurationService, EventPublisher $eventPublisher, CacheClient $cacheClient) {
            $this->expenseMapper = new ExpenseMapper($databaseClient);
            $this->httpClient = $httpClient;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->cacheClient = $cacheClient;
            $this->transactionManager = $databaseClient;
        }

        public function getExpensesForTrip(string $tripId) : array {
            return $this->expenseMapper->selectExpensesForTrip($tripId);
        }

        public function getExpense(string $expenseId) : ?Expense {
            return $this->expenseMapper->selectExpense($expenseId);
        }

        // TODO: Replace string $type by ExpenseType $type.
        public function createExpense(string $tripId, float $value, string $currency, string $type, string $description, ?string $subscriptionId) : Expense {                      
            $exchangeRate = $this->getExchangeRate($currency);

            // TODO: This is inacurrate, the subscription share is not included in the main currency value.
            $expense = new Expense(null, $description, $value, $currency, $exchangeRate, ExpenseType::from($type), $exchangeRate * $value,
                $subscriptionId === null ? null : $this->expenseMapper->selectSubscription($subscriptionId));
            $this->transactionManager->executeAtomically(function() use(&$expense, &$tripId, &$subscriptionId) {
                $this->expenseMapper->insertExpense($expense, $tripId, $subscriptionId);

                $this->eventPublisher->publish(Event::ExpenseCreated($expense->getId(), $tripId));
            });

            return $expense;
        }

        public function createSubscription(float $value, string $currency, string $description, int $expiration) : Subscription {                        
            $exchangeRate = $this->getExchangeRate($currency);

            $subscription = new Subscription(null, $description, $value, $currency, $exchangeRate, $expiration);
            $this->expenseMapper->insertSubscription($subscription);

            return $subscription;
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

        public function updateExpenseCurrency(string $expenseId, string $currency, string $tripId) : bool {
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

        private function getExchangeRate(string $currency) : float {       
            $mainCurrency = $this->configurationService->getConfigurationEntry("expensify")["mainCurrency"];
            if ($currency === $mainCurrency) {
                return 1;
            }

            $cachedExchangeRates = $this->cacheClient->get(self::EXCHANGE_RATE_CACHE_KEY);
            if ($cachedExchangeRates !== null) {
                return $cachedExchangeRates[$currency] ?? 0;
            }    
    
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT,
                EXCHANGE_RATE_API_KEY, $mainCurrency));         
            if ($apiResponse === null) {
                // TODO: Log error.
                return 0;
            }            

            $exchangeRates = array();
            foreach ($apiResponse["conversion_rates"] as $rawCurrency => $rawExchangeRate) {
                if ($rawCurrency !== $mainCurrency) {
                    $exchangeRates[$rawCurrency] = 1 / doubleval($rawExchangeRate);
                }
            }
            $this->cacheClient->set(self::EXCHANGE_RATE_CACHE_KEY, $exchangeRates, self::EXCHANGE_RATE_CACHE_TTL);

            return $exchangeRates[$currency] ?? 0;
        }
    }
?>