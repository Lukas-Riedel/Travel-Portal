<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class GetLandmarkProcessor extends Processor {        
        public function process($input) {
            $detectedLandmark = NULL;

            $apiResponse = $this->detectLandmark($input["photoId"]);

            if (array_key_exists("responses", $apiResponse) && count($apiResponse["responses"]) > 0) {
                if (array_key_exists("landmarkAnnotations", $apiResponse["responses"][0]) && count($apiResponse["responses"][0]["landmarkAnnotations"]) > 0) {
                    $detectedLandmark = $apiResponse["responses"][0]["landmarkAnnotations"][0]["description"];
                }
            }

           return $detectedLandmark;
        }

        public function getRequiredArguments() {
            return array("photoId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
        
        private function detectLandmark($photoId) {
            global $databaseProvider;

            $externalId = $databaseProvider
                ->statementBuilder("SELECT external_id FROM photo_identifier WHERE id = ?")
                ->withParameters($photoId)
                ->getSingleColumn("external_id");

            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "GET", 
                    "url" => "https://photoslibrary.googleapis.com/v1/mediaItems/" . $externalId));
                    
            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }
            
            $payload = array(
                "requests" => array(array(
                    "image" => array(
                        "content" => base64_encode(file_get_contents($apiResponse["baseUrl"] . "=d"))), 
                        "features" => array(array(
                            "maxResults" => 1, 
                            "type" => "LANDMARK_DETECTION")))));    

            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://vision.googleapis.com/v1/images:annotate", 
                    "payload" => json_encode($payload)));
        }
    }
?>