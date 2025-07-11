<?php
    class GetCoordinatesHandler extends Handler {
        public function handle($input, $roles) {
            global $geocodingService;

            $response = $geocodingService->getLocation($input["address"]);
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Coordinates";
        }

        public function getPath() {
            return "/coordinates/{address}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("address", "string", "Praha, Česko"));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve coordinates for the specified address";
        }
        
        public function getLongDescription() {
            return "Retrieves coordinates for the specified address.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Resolved location", 200, '{"country":"Česko","latitude":50.0755381,"longitude":14.4378005,"timezone":"Europe/Prague"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>