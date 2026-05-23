<?php
    namespace Core\Client\ExchangeRate;

    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Monolog\Logger;

    class ExchangeRateApiExchangeRateClient implements ExchangeRateClient {

        private const GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT = "https://v6.exchangerate-api.com/v6/%s/latest/%s";

        private readonly HttpClient $httpClient;
        private readonly Logger $logger;

        private readonly string $exchangeRateApiKey;
    
        public function __construct(HttpClient $httpClient, Logger $logger, string $exchangeRateApiKey) {
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->exchangeRateApiKey = $exchangeRateApiKey;
        }
        
        public function getExchangeRates(string $currency) : array {
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_EXCHANGE_RATE_API_ENDPOINT_FORMAT,
                $this->exchangeRateApiKey, $currency));         

            if (isset($apiResponse["error"])) {
                $this->logger->error("An unknown error occurred when fetching exchange rates.", array("error" => $apiResponse));
                return array();
            }            

            $exchangeRates = array();
            foreach ($apiResponse["conversion_rates"] as $rawCurrency => $rawExchangeRate) {
                if ($rawCurrency !== $currency) {
                    $exchangeRates[$rawCurrency] = 1 / doubleval($rawExchangeRate);
                }
            }

            return $exchangeRates;
        }
    }
?>