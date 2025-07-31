<?php
    namespace Service\Service\Expense;

    use Service\Service\Configuration\ConfigurationService;

    class ExpenseService {

        private const GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT = "https://v6.exchangerate-api.com/v6/%s/latest/%s";
        
        private const EXCHANGE_RATE_VALIDITY_SECONDS = 86400;

        private readonly ExpenseMapper $expenseMapper;
        
        private readonly \HttpClient $httpClient;
        
        private readonly ConfigurationService $configurationService;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \HttpClient $httpClient,
            ConfigurationService $configurationService, \EventPublisher $eventPublisher) {
            $this->expenseMapper = new ExpenseMapper($databaseProvider);
            $this->httpClient = $httpClient;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
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
            $expense = new Expense(NULL, $description, $value, $currency, $exchangeRate, ExpenseType::from($type), $exchangeRate * $value,
                $subscriptionId === NULL ? NULL : $this->expenseMapper->selectSubscription($subscriptionId));
            $this->expenseMapper->insertExpense($expense, $tripId, $subscriptionId);

            $this->eventPublisher->publishExpenseCreatedEvent($expense->getId(), $tripId);

            return $expense;
        }

        public function createSubscription(float $value, string $currency, string $description, int $expiration) : Subscription {                        
            $exchangeRate = $this->getExchangeRate($currency);

            $subscription = new Subscription(NULL, $description, $value, $currency, $exchangeRate, $expiration);
            $this->expenseMapper->insertSubscription($subscription);

            return $subscription;
        }

        public function getActiveSubscriptions() : array {
            return $this->expenseMapper->selectActiveSubscriptions();
        }
 
        public function updateExpenseDescription(string $expenseId, string $description, string $tripId) : bool {   
            $wasUpdated = $this->expenseMapper->updateExpenseDescription($expenseId, $description);
                
            $this->eventPublisher->publishExpenseUpdatedEvent($expenseId, $tripId);

            return $wasUpdated;
        }

        public function updateExpenseValue(string $expenseId, float $value, string $tripId) : bool {            
            $wasUpdated = $this->expenseMapper->updateExpenseValue($expenseId, $value);
                
            $this->eventPublisher->publishExpenseUpdatedEvent($expenseId, $tripId);

            return $wasUpdated;
        }

        public function updateExpenseCurrency(string $expenseId, string $currency, string $tripId) : bool {
            $exchangeRate = $this->getExchangeRate($currency);

            $wasUpdated = $this->expenseMapper->updateExpenseCurrency($expenseId, $currency, $exchangeRate);
                
            $this->eventPublisher->publishExpenseUpdatedEvent($expenseId, $tripId);

            return $wasUpdated;
        }

        public function removeExpense(string $expenseId, string $tripId) : bool {
            $wasDeleted = $this->expenseMapper->deleteExpense($expenseId) > 0;
                
            $this->eventPublisher->publishExpenseRemovedEvent($expenseId, $tripId);

            return $wasDeleted;
        }

        private function getExchangeRate(string $currency) : float {       
            $mainCurrency = $this->configurationService->getConfigurationEntry("mainCurrency");
            if ($currency === $mainCurrency) {
                return 1;
            }

            $cachedExchangeRate = $this->expenseMapper->selectExchangeRate($currency);    
            if ($cachedExchangeRate !== NULL) {
                return $cachedExchangeRate;
            }    
    
            $apiResponse = $this->httpClient->executeRequest(\HttpMethod::GET, sprintf(self::GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT, EXCHANGE_RATE_API_KEY, $mainCurrency));         
            if ($apiResponse === NULL) {
                return 0;
            }

            foreach ($apiResponse["conversion_rates"] as $rawCurrency => $rawExchangeRate) {
                if ($rawCurrency !== $mainCurrency) {
                    $this->expenseMapper->insertExchangeRate($rawCurrency, 1 / doubleval($rawExchangeRate), self::EXCHANGE_RATE_VALIDITY_SECONDS);
                }
            }

            $exchangeRate = $this->expenseMapper->selectExchangeRate($currency);
            return $exchangeRate !== NULL ? $exchangeRate : 0;
        }
    }
?>