<?php
    require_once(dirname(__FILE__) . "/../model/YearIdentifier.php");

    class ChangeYearIdentifierProcessor extends Processor {
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            if (isset($input["mainHighlightId"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE year_identifier SET main_highlight_id = ? WHERE id = ? AND EXISTS(SELECT * FROM highlight_year WHERE highlight_id = ? AND id = ?)")
                    ->withParameters($input["mainHighlightId"], $input["year"], $input["mainHighlightId"], $input["year"])
                    ->execute();
            }

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "YEAR", 
                    "id" => $input["year"]), NULL);    

            $yearRow = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier WHERE id = ?")
                ->withParameters($input["year"])
                ->getSingleRow();

            return new YearIdentifier($yearRow["id"], $this->getHighlight($yearRow["main_highlight_id"]));
        }

        public function getRequiredArguments() {
            return array("year");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $mainHighlightIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();
            
           return $mainHighlightIdentifierRow == NULL ? NULL : new Highlight($mainHighlightIdentifierRow["id"], $mainHighlightIdentifierRow["thumbnail_url"], $mainHighlightIdentifierRow["full_url"], 
                $mainHighlightIdentifierRow["focal_length"], $mainHighlightIdentifierRow["aperture"], $mainHighlightIdentifierRow["shutter_speed"], $mainHighlightIdentifierRow["iso"], $mainHighlightIdentifierRow["timestamp"]);
        }
    }
?>