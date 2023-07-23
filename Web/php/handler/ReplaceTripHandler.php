<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class ReplaceTripHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
            
            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }
    
            $response = $processorProvider->run("LoadTrip", $input);
            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Trips";
        }

        public function getPath() {
            return "/trips/{tripId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 128));
        }

        public function getMethod() {
            return "PUT";
        }

        public function getOperationId() {
            return "replace_trip";
        }
        
        public function getShortDescription() {
            return "Replace a trip with the specified identifier with a candidate trip";
        }
        
        public function getLongDescription() {
            return "Replaces a trip with the specified identifier with a candidate trip.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Replace trip", '{"candidateTripId":144}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Replaced trip", 200, '{"id":274,"name":"Barcelona a Madrid","year":2024,"start":1732575600,"end":1733094000,"countries":null,"imageUrl":null,"cost":0,"days":{"total":6,"working":4},"vacation":{"expected":2.75,"maximum":1.8734177215189867},"expenses":[],"stays":[],"flights":[],"watchedFlights":[],"layovers":[],"fitness":[],"notes":[],"publicHolidays":[]}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>