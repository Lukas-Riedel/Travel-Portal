<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class CreateFileProcessor extends Processor {
        public function process($input) {
            $separator = "mpr_separator";

            $metadata = array("name" => $input["name"]);
            if (isset($input["folderId"])) {
                $metadata["parents"] = array($input["folderId"]);
            }

            $payload = "--" . $separator . "\n"
                 . "Content-Type: application/json\n\n"
                 . json_encode($metadata) . "\n\n"
                 . "--" . $separator . "\n"
                 . "Content-Type: " . $input["contentType"] . "\n\n" 
                 . $input["content"] . "\n"
                 . "--" . $separator . "--";
    
            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart", 
                    "payload" => $payload, 
                    "contentType" => "multipart/related;boundary=" . $separator));
        }

        public function getRequiredArguments() {
            return array("name", "content", "contentType");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>