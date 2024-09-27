<?php
    class GetCategoryHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetCategories", $input);
            if ($response instanceof TargetError) {
                return $this->createResponse(NULL, $response);
            }
            if (count($response) == 1) {
                return $this->createResponse(200, $response[0]);
            }

            return $this->create404Response("categories", $input["categoryId"]);
        }

        public function getTag() {
            return "Categories";
        }

        public function getPath() {
            return "/categories/{categoryId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("categoryId", "integer", 1));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "get_category";
        }
        
        public function getShortDescription() {
            return "Retrieve a category with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Retrieves a category with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Category", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>