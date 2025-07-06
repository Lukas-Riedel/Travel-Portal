<?php
    require_once(dirname(__FILE__) . "/GetAirlineHandler.php");

    class UpdateAirlineHandler extends Handler {
        public function handle($input) {
            global $flightService, $databaseProvider;

            $response = (new GetAirlineHandler())
                ->handle(array(
                    "code" => $input["code"]));                    
            if ($response["code"] != 200) {
                return $response;
            }

            if (isset($input["name"])) {
                $flightService->updateAirlineName($input["code"], $input["name"]);
            }

            if (isset($input["logo"])) {
                $flightService->updateAirlineLogo($input["code"], $input["logo"]);
            }

            $databaseProvider->materializeViews();
    
            return (new GetAirlineHandler())
                ->handle(array(
                    "code" => $input["code"]));
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
            return "/airlines/{code}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("code", "string", "LH"));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update an airline with the specified code";
        }
        
        public function getLongDescription() {
            return "Updates an airline with the specified code.";
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