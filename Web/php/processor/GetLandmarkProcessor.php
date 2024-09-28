<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class GetLandmarkProcessor extends Processor {        
        public function process($input) {
            global $configuration;

            $detectedLandmark = "UNKNOWN";

            $image = file_get_contents($input["baseUrl"] . "=w" . $configuration["albumThumbnailImageSize"]["width"] . "-h" . $configuration["albumThumbnailImageSize"]["height"]);
            $apiResponse = $this->getGoogleVisionResponse(base64_encode($image));

            if (array_key_exists("error", $apiResponse)) {
                $detectedLandmark = $apiResponse["error"]["status"] . " - " . $apiResponse["error"]["message"];
            }

            if (array_key_exists("responses", $apiResponse) && count($apiResponse["responses"]) > 0) {
                if (array_key_exists("landmarkAnnotations", $apiResponse["responses"][0]) && count($apiResponse["responses"][0]["landmarkAnnotations"]) > 0) {
                    $detectedLandmark = $apiResponse["responses"][0]["landmarkAnnotations"][0]["description"];
                }
            }

           return $detectedLandmark;
        }

        public function getRequiredArguments() {
            return array("baseUrl");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
        
        private function getGoogleVisionResponse($base64Image) {
            $payload = array(
                "requests" => array(array(
                    "image" => array(
                        "content" => $base64Image), 
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