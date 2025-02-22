<?php
    require_once(dirname(__FILE__) . "/ExpenseMapper.php");
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/../model/Subscription.php");

    class ExpenseService {

        private const GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT = "https://v6.exchangerate-api.com/v6/%s/latest/%s";

        private readonly ExpenseMapper $expenseMapper;
        
        private readonly HttpClient $httpClient;
        
        private readonly ConfigurationService $configurationService;

        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseProvider $databaseProvider, HttpClient $httpClient,
            ConfigurationService $configurationService, EventPublisher $eventPublisher) {
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

            $expense = new Expense(NULL, $description, $value, $currency, $exchangeRate, $type);
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
            return $this->expenseMapper->selectAllActiveSubscriptions();
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
            $mainCurrency = $this->configurationService->getConfigurationForType("mainCurrency");
            if ($currency === $mainCurrency) {
                return 1;
            }

            $cachedExchangeRate = $this->expenseMapper->selectExchangeRate($currency);    
            if ($cachedExchangeRate !== NULL) {
                return $cachedExchangeRate;
            }    
    
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT,
                $this->configurationService->getConfigurationForType("exchangeRateApiKey"), $mainCurrency));         
            if ($apiResponse === NULL) {
                return 0;
            }

            $validity = $this->configurationService->getConfigurationForTypeAndKey("autoPurgeRetentionDays", "exchangeRates") * 86400;
            foreach ($apiResponse["conversion_rates"] as $currency => $rawExchangeRate) {
                if ($currency !== $mainCurrency) {
                    $this->expenseMapper->insertExchangeRate($currency, 1 / doubleval($rawExchangeRate), $validity);
                }
            }

            $exchangeRate = $this->expenseMapper->selectExchangeRate($currency);
            return $exchangeRate !== NULL ? $exchangeRate : 0;
        }
    }

    enum ExpenseType : string {
        case Flight = "FLIGHT";
        case Hotel = "HOTEL";
        case Attraction = "ATTRACTION";
        case IntercityTransport = "INTERCITY_TRANSPORT";
        case PublicTransport = "PUBLIC_TRANSPORT";
        case OrganizedTour = "ORGANIZED_TOUR";
        case CarRental = "CAR_RENTAL";
        case Fuel = "FUEL";
        case CityTax = "CITY_TAX";
        case Parking = "PARKING";
        case AirportTransfer = "AIRPORT_TRANSFER";
        case Visa = "VISA";
        case Other = "OTHER";

        public static function values(): array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>