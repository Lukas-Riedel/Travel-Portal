<?php
    require_once(dirname(__FILE__) . "/GetChatResponseProcessor.php");

    class GetSuggestedMapPointsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $cachedResult = $databaseProvider
                ->statementBuilder("SELECT json FROM cache_point WHERE place_id = ?")
                ->withParameters($input["placeId"])
                ->getSingleColumn("json");

            if ($cachedResult != NULL) {
                return json_decode($cachedResult, TRUE);
            }
        
            $placeRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();

            $points = $this->getSuggestedMapPoints($placeRow);
            if ($points == NULL) {
                return array();
            }

            return $points;
        }

        public function getRequiredArguments() {
            return array("placeId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function getSuggestedMapPoints($placeRow, $attempt = 1) {
            global $configuration, $databaseProvider;

            if ($attempt > 3) {
                return NULL;
            }

            $result = json_decode((new GetChatResponseProcessor())
                ->process(array(
                    "query" => sprintf($configuration["chatRequests"]["mapPoints"], $placeRow["name"], $placeRow["country"]))), TRUE);

            if ($result == NULL) {
                return $this->getSuggestedMapPoints($placeRow, $attempt + 1);
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO cache_point (place_id, json, timestamp) VALUES (?, ?, UNIX_TIMESTAMP())")
                ->withParameters($placeRow["id"], json_encode($result))
                ->execute();

            return $result == NULL ?  : $result;
        }
    }
?>