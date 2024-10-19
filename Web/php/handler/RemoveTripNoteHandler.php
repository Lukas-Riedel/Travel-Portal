<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class RemoveTripNoteHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $processorProvider->run("RemoveNote", $input);
            if ($response === FALSE) {                
                return $this->create404Response("trip_notes", $input["noteId"]);
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
            return "Trip Notes";
        }

        public function getPath() {
            return "/trips/{tripId}/notes/{noteId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 125),
                $this->createPathParameter("noteId", "integer", 83));
        }

        public function getMethod() {
            return "DELETE";
        }

        public function getOperationId() {
            return "remove_trip_note";
        }
        
        public function getShortDescription() {
            return "Remove a note with the specified identifier for the specified trip";
        }
        
        public function getLongDescription() {
            return "Removes a note with the specified identifier for the specified trip.";
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