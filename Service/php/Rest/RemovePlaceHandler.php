<?php
    class RemovePlaceHandler extends Handler {
        public function handle($input, $roles) {
            global $placeService;

            $response = FALSE;
            if ($input["type"] == "permanent") {
                $response = $placeService->removePermanentPlace($input["placeId"]);
            }
            if ($input["type"] == "candidate") {
                $response = $placeService->removeCandidatePlace($input["placeId"]);
            }
            
            if ($response === FALSE) {                
                return $this->create404Response("places", $input["placeId"]);
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
            return "Places";
        }

        public function getPath() {
            return "/places/{placeId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507),
                $this->createQueryParameter("type", "string", array("permanent", "candidate"), TRUE));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a place with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Removes a place with the specified identifier. This can only remove candidate and permanent places. To remove a place event, it is necessary to do so in the associated Google Calendar account.";
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