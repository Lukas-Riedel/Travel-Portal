<?php
    require_once(dirname(__FILE__) . "/../model/Category.php");
    require_once(dirname(__FILE__) . "/../model/HighlightIdentifier.php");
    require_once(dirname(__FILE__) . "/GetStatsProcessor.php");

    class GetCategoriesProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["categoryId"])) {
                $whereClauseBuilder->withClause("id = ?", $input["categoryId"]);
            }
            if (isset($input["categories"])) {
                $whereClauseBuilder->withClause("FIND_IN_SET(category, ?)", $input["categories"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $categoryRows = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            $result = array();

            foreach ($categoryRows as &$categoryRow) {
                $highlights = array();
                $stats = array();
                
                $includeHighlights = isset($input["includeHighlights"]) && $input["includeHighlights"] == "true";
                if ($includeHighlights || isset($input["categoryId"])) {
                    $highlights = $this->getHighlights($categoryRow);                      
                }

                $includeStats = isset($input["includeStats"]) && $input["includeStats"] == "true";
                if ($includeStats || isset($input["categoryId"])) {
                    $stats = $this->getStats($categoryRow);                      
                }
                
                $result[] = new Category($categoryRow["id"], $categoryRow["name"], $categoryRow["category"], $this->getHighlight($categoryRow["main_highlight_id"]), $highlights, $stats);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getHighlights($categoryRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_category hc INNER JOIN highlight_identifier hi ON hc.highlight_id = hi.id LEFT JOIN photo p ON hi.photo_id = p.id WHERE hc.id = ?")
                ->withParameters($categoryRow["id"])
                ->getMappedResultSet(function ($highlightRow) { 
                    return new HighlightIdentifier($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["focal_length"], 
                        $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
                });
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();
            
           return $highlightRow == NULL ? NULL : new HighlightIdentifier($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $highlightRow["focal_length"], $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
        }
        
        private function getStats($categoryRow) {
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "category", 
                    "id" => $categoryRow["id"]));
        }
    }
?>