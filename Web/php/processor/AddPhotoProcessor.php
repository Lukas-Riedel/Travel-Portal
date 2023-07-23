<?php  
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetPhotoIdentifierProcessor.php");

    class AddPhotoProcessor extends Processor {   
        public function process($input) {
            global $databaseProvider;
        
            // Upload bytes.
            $uploadToken = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/uploads", 
                    "contentType" => "application/octet-stream",
                    "headers" => array(
                        "X-Goog-Upload-Content-Type" => "image/jpeg",
                        "X-Goog-Upload-Protocol" => "raw"),
                    "payload" => base64_decode($input["data"])));    
        
            if ($uploadToken == NULL) {
                throw new RuntimeException("The photo was not uploaded.");
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO photo_pending (album_id, file_name, position, upload_token) VALUES (?, ?, ?, ?)")
                ->withParameters($input["albumId"], $input["name"], $input["position"], $uploadToken)
                ->execute();

            return TRUE;             
        }

        public function getRequiredArguments() {
            return array("albumId", "name", "data", "position");
        }

        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>