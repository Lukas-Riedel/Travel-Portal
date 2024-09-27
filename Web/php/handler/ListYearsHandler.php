<?php
    class ListYearsHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetYears", $input);
            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Years";
        }

        public function getPath() {
            return "/years";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("includeStats", "boolean", "false"));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "list_years";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of years";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of years. Some fields in the result may be omitted due to performance reasons, these can be enabled by various include filters.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Years", 200, '[]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample());
        }
    }
?>