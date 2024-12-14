<?php
    require_once(dirname(__FILE__) . "/../model/Photo.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetPhotoIdentifierProcessor.php");

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
            return new Photo($photoId, NULL, $photoRow["permalink"], $photoRow["focal_length"], $photoRow["aperture"],
                $photoRow["shutter_speed"], $photoRow["iso"], $photoRow["timestamp"]);
        }

        public function uploadPhoto($fileName, $albumId, $position, $data) : bool {
            global $databaseProvider;
        
            $uploadToken = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/uploads", 
                    "contentType" => "application/octet-stream",
                    "headers" => array(
                        "X-Goog-Upload-Content-Type" => "image/jpeg",
                        "X-Goog-Upload-Protocol" => "raw"),
                    "payload" => base64_decode($data)));    
        
            if ($uploadToken === NULL) {
                throw new RuntimeException("The photo " . $fileName . " was not uploaded.");
            }

            return $databaseProvider
                ->statementBuilder("INSERT INTO photo_pending (album_id, file_name, position, upload_token) VALUES (?, ?, ?, ?)")
                ->withParameters($albumId, $fileName, $position, $uploadToken)
                ->execute() === 1;
        }
    }
?>