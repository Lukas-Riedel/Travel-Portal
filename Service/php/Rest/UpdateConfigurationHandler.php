<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class UpdateConfigurationHandler extends Handler {
        public function handle($input) {
            global $configurationService;

            $response = $configurationService->updateConfigurationEntry($input["key"], $input["value"]);
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Configuration";
        }

        public function getPath() {
            return "/configuration/{key}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("key", "string", "calendars"));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update a configuration item with the specified key";
        }
        
        public function getLongDescription() {
            return "Updates a configuration item with the specified key.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update configuration item", '{"trips":"...","places":"...","stays":"...","flights":"...","watchedFlights":"..."}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated configuration item", 200, '{"trips":"...","places":"...","stays":"...","flights":"...","watchedFlights":"..."}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>