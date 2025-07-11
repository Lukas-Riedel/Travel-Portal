<?php
    class ListAirlinesHandler extends Handler {
        public function handle($input, $roles) {
            global $flightService;

            $response = $flightService->getAirlineIdentifiers();
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Airlines";
        }

        public function getPath() {
            return "/airlines";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of airlines";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of airlines.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Airlines", 200, '[]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>