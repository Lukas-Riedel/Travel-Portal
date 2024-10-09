<?php 
    require_once(dirname(__FILE__) . "/../model/YearIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/HighlightIdentifier.php");

    class GetYearIdentifierProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $yearIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier WHERE id = ?")
                ->withParameters($input["year"])
                ->getFirstRow();

            if ($yearIdentifierRow != NULL) {
                return new YearIdentifier($yearIdentifierRow["id"], $this->getHighlight($yearIdentifierRow["main_highlight_id"]));
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO year_identifier (id) VALUES (?)")
                ->withParameters($input["year"])
                ->execute();

            $yearIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier WHERE id = ?")
                ->withParameters($input["year"])
                ->getFirstRow();
                
            return new YearIdentifier($yearIdentifierRow["id"], $this->getHighlight($yearIdentifierRow["main_highlight_id"]));
        }

        public function getRequiredArguments() {
            return array("year");
        }

        public function requiresAdminRole() {
            return FALSE;
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $mainHighlightIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();
            
           return $mainHighlightIdentifierRow == NULL ? NULL : new HighlightIdentifier($mainHighlightIdentifierRow["id"], $mainHighlightIdentifierRow["thumbnail_url"], $mainHighlightIdentifierRow["full_url"], 
                $mainHighlightIdentifierRow["focal_length"], $mainHighlightIdentifierRow["aperture"], $mainHighlightIdentifierRow["shutter_speed"], $mainHighlightIdentifierRow["iso"], $mainHighlightIdentifierRow["timestamp"]);
        }
    }
?>