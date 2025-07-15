<?php

    class CreateAirlineHandler extends Handler {
        public function handle($input, $roles) {
            global $flightService;

            $airline = $flightService->createAirline($input["name"], isset($input["logo"]) ? $input["logo"] : NULL);
            return $this->createResponse(201, $airline);
        }

        public function getRequiredRole() {
            return "ADMIN";
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
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create an airline";
        }
        
        public function getLongDescription() {
            return "Creates an airline.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("airline", '{"name":"Lufthansa","logo":"SVG Content"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created airline", 201, '{"id":"4","name":"Lufthansa","code":[],"logo":"SVG Content"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>