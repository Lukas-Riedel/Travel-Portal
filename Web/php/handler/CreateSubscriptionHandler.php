<?php
    class CreateSubscriptionHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
    
            $response = $processorProvider->run("AddSubscription", $input);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
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
            return "POST";
        }

        public function getOperationId() {
            return "create_subsciption";
        }
        
        public function getShortDescription() {
            return "Create a subscription";
        }
        
        public function getLongDescription() {
            return "Creates a subscription. The auto-generated subscription identifier can then be used to create a trip expense, the main purpose being an option to equally split expenses among multiple trips. The exchange rate to the configured main currency is fetched when creating the subscription. The subscription is never deleted, it can only expire, however, trip expenses can refer also to expired subscriptions.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create subscription", '{"description":"Deutschland Ticket","value":49,"currency":"EUR","expiration":1725098400}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created subscription", 201, '{"id":9,"description":"Deutschland Ticket","value":49,"currency":"EUR","mainCurrencyValue":1227.1475081392311,"expiration":"1725098400"}'),
                $this->create400ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>