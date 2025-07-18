<?php
    class ListLabelsHandler extends Handler {
        public function handle($input) {
            global $labelService;

            $response = $labelService->getLabels();
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Labels";
        }

        public function getPath() {
            return "/labels";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of labels";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of labels.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Labels", 200, '[]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>