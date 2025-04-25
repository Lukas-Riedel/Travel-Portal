<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class RemoveTripHandler extends Handler {
        public function handle($input) {
            global $placeService, $tripService;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $wasDeleted = ($response["body"]->getYear() !== NULL)
                ? $tripService->archiveTrip($input["tripId"])
                : $placeService->removeCandidateEventsForCandidateTrip($input["tripId"]);
            
            if ($wasDeleted === FALSE) {                
                return $this->create404Response("trips", $input["tripId"]);
            }

            return $this->createResponse(204, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
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
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>