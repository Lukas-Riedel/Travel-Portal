<?php
    require_once(dirname(__FILE__) . "/GetCategoryHandler.php");

    class CreateCategoryHighlightHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $processorProvider->run("AddHighlight", array(
                "id" => $input["categoryId"],
                "type" => "category",
                "photoId" => $input["photoId"]));
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Category Highlights";
        }

        public function getPath() {
            return "/categories/{categoryId}/highlights";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("categoryId", "integer", 2));
        }

        public function getMethod() {
            return "POST";
        }

        public function getOperationId() {
            return "create_category_highlight";
        }
        
        public function getShortDescription() {
            return "Create a highlight for the specified category";
        }
        
        public function getLongDescription() {
            return "Creates a highlight for the specified category.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create highlight", '{"photoId":16257}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created highlight", 201, '{"id":386,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2nqM7MqQXG1vjmMZVWfs9ghZ0o8_eZTQIEPYFynJ_HFc5IB54-2zK4FROxRVoDAq5_s2Exq0YX0QWqhezCLKd3JJumerg.jpg","full":null},"focalLength":20,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1692798660}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>