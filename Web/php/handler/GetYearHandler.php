<?php
    class GetYearHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetYears", $input);
            if ($response instanceof TargetError) {
                return $this->createResponse(NULL, $response);
            }
            if (count($response) == 1) {
                return $this->createResponse(200, $response[0]);
            }

            return $this->create404Response("years", $input["year"]);
        }

        public function getTag() {
            return "Years";
        }

        public function getPath() {
            return "/years/{year}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("year", "integer", 2024));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "get_year";
        }
        
        public function getShortDescription() {
            return "Retrieve a year with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Retrieves a year with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Year", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>