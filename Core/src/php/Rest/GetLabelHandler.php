<?php
    class GetLabelHandler extends Handler {
        public function handle($input) {
            global $labelService;

            $response = $labelService->getLabel($input["labelId"]);
            if ($response !== null) {
                return $this->createResponse(200, $response);
            }

            return $this->create404Response("labels", $input["labelId"]);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return true;
        }

        public function getTag() {
            return "Labels";
        }

        public function getPath() {
            return "/labels/{labelId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("labelId", "string", "1"));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a label with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Retrieves a label with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Label", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>