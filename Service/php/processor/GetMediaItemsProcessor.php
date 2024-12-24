<?php
    require_once(dirname(__FILE__) . "/../model/Photo.php");

    class GetMediaItemsProcessor extends Processor {        
        public function process($input) {
            global $photoService;

            return $photoService->getPhotos($input["albumId"]);
        }

        public function getRequiredArguments() {
            return array("albumId");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>