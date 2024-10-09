<?php
    require_once(dirname(__FILE__) . "/../model/Year.php");
    require_once(dirname(__FILE__) . "/GetStatsProcessor.php");

    class GetYearsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["year"])) {
                $whereClauseBuilder->withClause("id = ?", $input["year"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $years = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            $result = array();

            foreach ($years as &$year) {
                $highlights = array();
                $stats = array();

                $includeStats = isset($input["includeStats"]) && $input["includeStats"] == "true";
                if ($includeStats || isset($input["year"])) {
                    $stats = $this->getStats($year["id"]);                      
                }

                $includeHighlights = isset($input["includeHighlights"]) && $input["includeHighlights"] == "true";
                if ($includeHighlights || isset($input["year"])) {
                    $highlights = $this->getHighlights($year["id"]);                      
                }

                $result[] = new Year($year["id"], $this->getHighlight($year["main_highlight_id"]), $highlights, $stats);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
        
        private function getStats($year) {
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "year", 
                    "id" => $year));
        }

        private function getHighlights($year) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_year hy INNER JOIN highlight_identifier hi ON hy.highlight_id = hi.id LEFT JOIN photo p ON hi.photo_id = p.id WHERE hy.id = ?")
                ->withParameters($year)
                ->getMappedResultSet(function ($highlightRow) { 
                    return new HighlightIdentifier($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["focal_length"], 
                        $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
                });
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