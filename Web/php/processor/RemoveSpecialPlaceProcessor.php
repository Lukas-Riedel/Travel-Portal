<?php
    class RemoveSpecialPlaceProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM " . $this->resolveTable($input["type"]) . " WHERE place_id = ?")
                ->withParameters($input["placeId"])
                ->execute();

            if ($input["type"] == "permanent") {
                $categoryIdsToUpdate = $databaseProvider
                    ->statementBuilder("SELECT category_id FROM category WHERE place_id = ?")
                    ->withParameters($input["placeId"])
                    ->getResultSetForColumn("category_id");

                foreach ($categoryIdsToUpdate as &$categoryIdToUpdate) {
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "CATEGORY", 
                            "id" => $categoryIdToUpdate), NULL);
                }

                $yearsToUpdate = $databaseProvider
                    ->statementBuilder("SELECT DISTINCT YEAR(FROM_UNIXTIME(start)) AS year FROM place_summary WHERE place_id = ?")
                    ->withParameters($input["placeId"])
                    ->getResultSetForColumn("year");

                foreach ($yearsToUpdate as &$yearToUpdate) {
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "YEAR", 
                            "id" => $yearToUpdate), NULL);
                }
            }

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("type", "placeId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function resolveTable($type) {
            if ($type == "candidate") {
                return "place_candidate";
            }
            if ($type == "permanent") {
                return "place_permanent";
            }
            throw new InvalidArgumentException("Unknown place type " . $type . ". Permitted values: candidate, permanent");
        }
    }
?>