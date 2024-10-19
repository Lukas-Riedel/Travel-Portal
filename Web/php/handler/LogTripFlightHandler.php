<?php
    class LogTripFlightHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
    
            $response = $processorProvider->run("LogFlight", $input);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Trip Flights";
        }

        public function getPath() {
            return "/trips/{tripId}/flights/log";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 125));
        }

        public function getMethod() {
            return "POST";
        }

        public function getOperationId() {
            return "log_trip_flight";
        }
        
        public function getShortDescription() {
            return "Log a flight for the specified trip";
        }
        
        public function getLongDescription() {
            return "Logs a flight for the specified trip. If only general information about the flight are provided, then the rest is obtained via Flightradar24 APIs.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Log flight automatically", '{"flight":"FR1531","from":"Benátky","to":"Praha","scheduledDeparture":1725473100}'),
                $this->createRequestExample("Log flight manually", '{"flight":"FR1531","aircraft":"B738","registration":"SP-RKB","from":"Benátky","fromCode":"TSF","to":"Praha","toCode":"PRG","scheduledDeparture":1725473100,"actualDeparture":1725476732,"scheduledArrival":1725477600,"actualArrival":1725480116}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Logged flight", 201, '{"flight":"FR1531","registration":"SP-RKB","aircraft":"B738","distance":519.0304981094692,"from":{"id":62,"name":"Benátky","code":"TSF","country":"Itálie","latitude":45.6498881,"longitude":12.1944795,"timezone":"Europe/Rome"},"to":{"id":2,"name":"Praha","code":"PRG","country":"Česko","latitude":50.101791,"longitude":14.2631811,"timezone":"Europe/Prague"},"start":1725476732,"end":1725480116}'),
                $this->create400ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>