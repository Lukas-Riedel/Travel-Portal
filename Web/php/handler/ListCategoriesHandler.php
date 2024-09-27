<?php
    class ListCategoriesHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetCategories", $input);
            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Categories";
        }

        public function getPath() {
            return "/categories";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("categories", "string", "COUNTRY"),
                $this->createQueryParameter("includeStats", "boolean", "false"));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "list_categories";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of categories";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of categories matching the specified filters. Some fields in the result may be omitted due to performance reasons, these can be enabled by various include filters.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Categories", 200, '[]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample());
        }
    }
?>