<?php
    require_once(dirname(__FILE__) . "/GetChatResponseProcessor.php");
    
    class GetSuggestedExcerptProcessor extends Processor {        
        public function process($input) {
            global $configuration, $databaseProvider;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();

            return (new GetChatResponseProcessor())
                ->process(array(
                    "query" => sprintf($configuration["chatRequests"]["suggestedExcerpt"], $placeIdentifierRow["name"], $placeIdentifierRow["country"])));
        }

        public function getRequiredArguments() {
            return array("placeId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>