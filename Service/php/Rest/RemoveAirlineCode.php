<?php
    require_once(dirname(__FILE__) . "/GetAirlineHandler.php");

    class RemoveAirlineCode extends Handler {
        public function handle($input) {
            global $flightService;

            $response = (new GetAirlineHandler())
                ->handle(array(
                    "airlineId" => $input["airlineId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }

            $flightService->updateAirlineCodeAirline($input["airlineCode"], NULL);
            if ($response === FALSE) {                
                return $this->create404Response("airline_codes", $input["airlineCode"]);
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
            return "Airline Codes";
        }

        public function getPath() {
            return "/airlines/{airlineId}/codes/{airlineCode}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("airlineId", "integer", 2),
                $this->createPathParameter("airlineCode", "string", "W8"));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a code for the specified airline";
        }
        
        public function getLongDescription() {
            return "Removes a code for the specified airline.";
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