<?php
    class UpdateAlbumProcessor extends Processor {        
        public function process($input) {
            global $albumService;

            if (isset($input["albumId"])) {
                $albumService->updateAlbum($input["albumId"]);
            }
            else {
                $albumService->updateAlbums();
            }
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>