<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class UpdateConfigurationHandler extends Handler {
        public function handle($input) {
            global $configurationService;

            $response = $configurationService->updateConfigurationEntryValue($input["type"], isset($input["key"]) ? $input["key"] : NULL, $input["value"]);
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
            return "/configuration/{type}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("type", "string", "AIRLINES"));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update a configuration item with the specified type";
        }
        
        public function getLongDescription() {
            return "Updates a configuration item with the specified type.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update configuration item with a key", '{"key":"FR","value":"Ryanair"}'),
                $this->createRequestExample("Update configuration item without a key", '{"value":10}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated configuration item", 200, '{"airlines":{"FR":"Ryanair"}}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>