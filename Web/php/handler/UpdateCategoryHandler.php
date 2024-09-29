<?php
    require_once(dirname(__FILE__) . "/GetCategoryHandler.php");

    class UpdateCategoryHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }     

            $response = $processorProvider->run("ChangeCategoryIdentifier", $input);
            if ($response instanceof TargetError) {
                return $this->createResponse(NULL, $response);
            }
    
            return (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));
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
            return "PATCH";
        }

        public function getOperationId() {
            return "update_category";
        }
        
        public function getShortDescription() {
            return "Update a category with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates a category with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update category name", '{"name":"Evropa"}'),
                $this->createRequestExample("Update category main highlight", '{"mainHighlightId":1}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated category", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>