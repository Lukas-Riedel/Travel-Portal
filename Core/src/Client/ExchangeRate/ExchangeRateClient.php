<?php
    namespace Core\Client\ExchangeRate;

    interface ExchangeRateClient {
        public function getExchangeRates(string $currency) : array;
    }
?>