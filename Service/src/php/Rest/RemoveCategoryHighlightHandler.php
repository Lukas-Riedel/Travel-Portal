<?php
    require_once(__DIR__ . "/GetCategoryHandler.php");

    class RemoveCategoryHighlightHandler extends Handler {
        public function handle($input) {
            global $highlightService;

            $response = (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $highlightService->removeCategoryHighlight($input["categoryId"], $input["highlightId"]);
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
            return "/categories/{categoryId}/highlights/{highlightId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("categoryId", "integer", 2),
                $this->createPathParameter("highlightId", "integer", 769));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a highlight with the specified identifier for the specified category";
        }
        
        public function getLongDescription() {
            return "Removes a highlight with the specified identifier for the specified category.";
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