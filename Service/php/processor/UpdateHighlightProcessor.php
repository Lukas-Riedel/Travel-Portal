<?php
    class UpdateHighlightProcessor extends Processor {        
        public function process($input) {
            global $highlightService;
        
            if (isset($input["photoId"])) {
                $highlightService->updateHighlightForPhoto($input["photoId"]);
            }
            else if (isset($input["highlightId"])) {
                $highlightService->updateHighlight($input["highlightId"]);
            }
            else {
                $highlightService->updateHighlights();
            }
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>