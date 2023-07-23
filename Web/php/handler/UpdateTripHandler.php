<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class UpdateTripHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }
    
            if (isset($input["start"])) {
                $response = $processorProvider->run("MoveTrip", $input);
            }
            else {
                $response = $processorProvider->run("ChangeTripIdentifier", $input);
            }

            if ($response instanceof TargetError) {
                return $this->createResponse(NULL, $response);
            }

            return (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
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
            return "PATCH";
        }

        public function getOperationId() {
            return "update_trip";
        }
        
        public function getShortDescription() {
            return "Update a trip with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates a trip with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update trip name", '{"name":"Východní Itálie"}'),
                $this->createRequestExample("Update trip start", '{"start":1724666400}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated trip", 200, '{"id":274,"name":"Barcelona a Madrid","year":2024,"start":1732575600,"end":1733094000,"countries":null,"imageUrl":null,"cost":0,"days":{"total":6,"working":4},"vacation":{"expected":2.75,"maximum":1.8734177215189867},"expenses":[],"stays":[],"flights":[],"watchedFlights":[],"layovers":[],"fitness":[],"notes":[],"publicHolidays":[]}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>