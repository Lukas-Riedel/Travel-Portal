<?php
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");

    class ChangeCategoryIdentifierProcessor extends Processor {
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            if (isset($input["mainHighlightId"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE category_identifier SET main_highlight_id = ? WHERE id = ? AND EXISTS(SELECT * FROM highlight_category WHERE highlight_id = ? AND id = ?)")
                    ->withParameters($input["mainHighlightId"], $input["categoryId"], $input["mainHighlightId"], $input["categoryId"])
                    ->execute();
            }

            if (isset($input["name"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE category_identifier SET name = ? WHERE id = ?")
                    ->withParameters($input["name"], $input["categoryId"])
                    ->execute();
            }

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "CATEGORY", 
                    "id" => $input["categoryId"]), NULL);    

            $categoryRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE id = ?")
                ->withParameters($input["categoryId"])
                ->getSingleRow();

            return new CategoryIdentifier($categoryRow["id"], $categoryRow["name"], $categoryRow["category"], $this->getHighlight($categoryRow["main_highlight_id"]));
        }

        public function getRequiredArguments() {
            return array("categoryId");
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
            
           return $mainHighlightIdentifierRow == NULL ? NULL : new HighlightIdentifier($mainHighlightIdentifierRow["id"], $mainHighlightIdentifierRow["thumbnail_url"], $mainHighlightIdentifierRow["full_url"], 
                $mainHighlightIdentifierRow["focal_length"], $mainHighlightIdentifierRow["aperture"], $mainHighlightIdentifierRow["shutter_speed"], $mainHighlightIdentifierRow["iso"], $mainHighlightIdentifierRow["timestamp"]);
        }
    }
?>