<?php
    class UpdateCategoriesProcessor extends Processor {        
        public function process($input) {
            global $categoryService, $placeService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($input["placeId"]);
            $categoryService->updateCategories($placeIdentifier);
        }

        public function getRequiredArguments() {
            return array("placeId");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>