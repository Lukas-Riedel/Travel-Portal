<?php
    require_once(__DIR__ . "/GetLabelHandler.php");

    class UpdateLabelHandler extends Handler {
        public function handle($input) {
            global $labelService, $databaseProvider;

            $response = (new GetLabelHandler())
                ->handle(array(
                    "labelId" => $input["labelId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }

            if (isset($input["name"])) {
                $labelService->updateLabelName($input["labelId"], $input["name"]);
            }

            $databaseProvider->materializeViews();
            return (new GetLabelHandler())
                ->handle(array(
                    "labelId" => $input["labelId"]));
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Labels";
        }

        public function getPath() {
            return "/labels/{labelId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("labelId", "integer", 1));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update a label with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates a label with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update label name", '{"name":"Hlavní město"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated label", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>