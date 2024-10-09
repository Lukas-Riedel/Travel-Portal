<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class RemoveTripHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            if ($response["body"]->getYear() !== NULL) {
                $response = $processorProvider->run("ArchiveTrip", $input);
            }
            else {
                $response = $processorProvider->run("RemoveCandidateTrip", $input);
            }
            
            if ($response === FALSE) {                
                return $this->create404Response("trips", $input["tripId"]);
            }

            return $this->createResponse(204, $response);
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
            return "DELETE";
        }

        public function getOperationId() {
            return "remove_trip";
        }
        
        public function getShortDescription() {
            return "Remove a trip with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Removes a trip with the specified identifier. Candidate trips are deleted forever, event trips are archived.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>