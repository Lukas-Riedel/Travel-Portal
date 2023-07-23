<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");

    class GetChatResponseProcessor extends Processor {        
        public function process($input) {
            global $configuration;

            $payload = array(
                "model" => "gpt-4o", 
                "messages" => array(array(
                    "role" => "user", 
                    "content" => $input["query"])));

            return (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "payload" => json_encode($payload), 
                    "url" => "https://api.openai.com/v1/chat/completions",
                    "headers" => "Content-Type: application/json,Authorization: Bearer " . $configuration["openAiApiKey"]))["choices"][0]["message"]["content"];
        }

        public function getRequiredArguments() {
            return array("query");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>