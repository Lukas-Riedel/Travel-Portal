<?php
    require_once(dirname(__FILE__) . "/GetYearHandler.php");

    class RemoveYearHighlightHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetYearHandler())
                ->handle(array(
                    "year" => $input["year"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $processorProvider->run("RemoveHighlight", array(
                "id" => $input["year"],
                "type" => "year",
                "highlightId" => $input["highlightId"]));
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Year Highlights";
        }

        public function getPath() {
            return "/years/{year}/highlights/{highlightId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("year", "integer", 2024),
                $this->createPathParameter("highlightId", "integer", 769));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a highlight with the specified identifier for the specified year";
        }
        
        public function getLongDescription() {
            return "Removes a highlight with the specified identifier for the specified year.";
        }
        
        public function getRequestExamples() {
            return array();
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