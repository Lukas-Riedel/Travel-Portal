<?php
    require_once(dirname(__FILE__) . "/../model/Photo.php");

    class PhotoService {
        public function getPhoto($photoId) : ?Photo {
            global $databaseProvider;            
                
            $photoRow = $databaseProvider
                ->statementBuilder("SELECT * FROM photo WHERE id = ?")
                ->withParameters($photoId)
                ->getSingleRow();

            if ($photoRow === NULL) {
                return NULL;
            }

            // TODO: URL
            return new Photo($photoId, NULL, $photoRow["focal_length"], $photoRow["aperture"], $photoRow["shutter_speed"], $photoRow["iso"], $photoRow["timestamp"]);
        }
    }
?>