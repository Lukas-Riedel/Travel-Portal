<?php
    use Service\Service\Flight\FlightType;

    class CreateFlightHandler extends Handler {
        public function handle($input) {
            global $flightService;
            
            $type = isset($input["type"]) ? FlightType::from($input["type"]) : FlightType::Scheduled;
            unset($input["type"]); // TODO: Why is this?

            $response = $flightService->createFlight($type, $input["flight"], $input["from"], $input["to"], $input["scheduledDeparture"], $input["scheduledArrival"]);            
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Flights";
        }

        public function getPath() {
            return "/flights";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("type", "string", array("scheduled", "watched")));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a flight";
        }
        
        public function getLongDescription() {
            return "Creates a flight. A new calendar event is created for it.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create flight", '{"flight":"LH6750","from":"Frankfurt","to":"Montréal","scheduledDeparture":1750433700,"scheduledArrival":1750461900}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created flight", 201, '{"flight":"LH6750","registration":null,"aircraft":null,"distance":null,"from":{"id":null,"name":"Frankfurt","code":null,"country":null,"latitude":null,"longitude":null,"timezone":null},"to":{"id":null,"name":"Montréal","code":null,"country":null,"latitude":null,"longitude":null,"timezone":null},"start":1750433700,"end":1750461900}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>