<?php
    class ListSubscriptionsHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetActiveSubscriptions", $input);
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Subscriptions";
        }

        public function getPath() {
            return "/subscriptions";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "list_places";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of subscriptions";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of subscriptions. Only non-expired subscriptions are returned.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Non-expired subscriptions", 200, '[{"id":2,"description":"ESTA","value":21,"currency":"USD","mainCurrencyValue":489.72,"expiration":"1731369600"},{"id":8,"description":"WizzAir Club","value":39.99,"currency":"EUR","mainCurrencyValue":1014.45966514461,"expiration":"1752271200"}]'),
                $this->create400ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>