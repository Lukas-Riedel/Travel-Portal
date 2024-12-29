<?php
    class UpdateRegionAreasProcessor extends Processor {        
        public function process($input) {
            global $categoryService;

            $categoryService->updateRegionAreas();
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>