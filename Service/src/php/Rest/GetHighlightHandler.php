<?php
    class GetHighlightHandler extends Handler {
        public function handle($input) {
            global $highlightService;

            $response = $highlightService->getHighlight($input["highlightId"]);
            if ($response !== NULL) {
                return $this->createResponse(200, $response);
            }

            return $this->create404Response("highlights", $input["highlightId"]);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Highlights";
        }

        public function getPath() {
            return "/highlights/{highlightId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("highlightId", "integer", 1));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a highlight with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Retrieves a highlight with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Highlight", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>