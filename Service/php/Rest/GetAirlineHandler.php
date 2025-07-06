<?php
    class GetAirlineHandler extends Handler {
        public function handle($input) {
            global $flightService;

            $response = $flightService->getAirlineIdentifier($input["code"]);
            if ($response !== NULL) {
                return $this->createResponse(200, $response);
            }

            return $this->create404Response("airlines", $input["code"]);
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
            return "/airlines/{code}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("code", "string", "LH"));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve an airline with the specified code";
        }
        
        public function getLongDescription() {
            return "Retrieves an airline with the specified code.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Airline", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>