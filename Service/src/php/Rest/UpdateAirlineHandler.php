<?php
    require_once(__DIR__ . "/GetAirlineHandler.php");

    class UpdateAirlineHandler extends Handler {
        public function handle($input) {
            global $flightService, $databaseProvider;

            $response = (new GetAirlineHandler())
                ->handle(array(
                    "airlineId" => $input["airlineId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }

            if (isset($input["name"])) {
                $flightService->updateAirlineName($input["airlineId"], $input["name"]);
            }

            if (isset($input["logo"])) {
                $flightService->updateAirlineLogo($input["airlineId"], $input["logo"]);
            }

            $databaseProvider->materializeViews();
    
            return (new GetAirlineHandler())
                ->handle(array(
                    "airlineId" => $input["airlineId"]));
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
                $this->createPathParameter("airlineId", "string", "1"));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update an airline with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates an airline with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update airline name", '{"name":"Lufthansa"}'),
                $this->createRequestExample("Update airline logo", '{"logo":"SVG Content"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated airline", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>