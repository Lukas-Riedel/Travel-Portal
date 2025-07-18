<?php
    class RemoveAirlineHandler extends Handler {
        public function handle($input) {
            global $flightService;

            $response = $flightService->removeAirline($input["airlineId"]);
            if ($response === FALSE) {                
                return $this->create404Response("airlines", $input["airlineId"]);
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
            return "Airlines";
        }

        public function getPath() {
            return "/airlines/{airlineId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("airlineId", "integer", 41));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove an airline with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Removes an airline with the specified identifier.";
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