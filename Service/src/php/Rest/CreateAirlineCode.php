<?php
    require_once(__DIR__ . "/GetAirlineHandler.php");

    class CreateAirlineCode extends Handler {
        public function handle($input) {
            global $flightService;

            $response = (new GetAirlineHandler())
                ->handle(array(
                    "airlineId" => $input["airlineId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $flightService->updateAirlineCodeAirline($input["code"], $input["airlineId"]);
            return $this->createResponse(204, NULL);
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
            return "/airlines/{airlineId}/codes";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("airlineId", "integer", 2));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a code for the specified airline";
        }
        
        public function getLongDescription() {
            return "Creates a code for the specified airline.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create airline code", '{"code":"W8"}'));
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