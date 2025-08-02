<?php
    require_once(__DIR__ . "/GetPlaceHandler.php");

    class ReplaceConfigurationHandler extends Handler {
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
            return "PUT";
        }
        
        public function getShortDescription() {
            return "Replace a configuration item with the specified key";
        }
        
        public function getLongDescription() {
            return "Replaces a configuration item with the specified key.";
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