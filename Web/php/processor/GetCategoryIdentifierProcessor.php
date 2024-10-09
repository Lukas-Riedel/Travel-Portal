<?php 
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/HighlightIdentifier.php");

    class GetCategoryIdentifierProcessor extends Processor {  
        public function process($input) {
            global $databaseProvider;
            
            $categoryIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE name = ?")
                ->withParameters($input["name"])
                ->getFirstRow();

            if ($categoryIdentifierRow != NULL) {
                return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], $categoryIdentifierRow["category"], $this->getHighlight($categoryIdentifierRow["main_highlight_id"]));
            }

            if (!isset($input["category"])) {
                throw new InvalidArgumentException("The category must be specified when creating an identifier.");
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO category_identifier (name, category) VALUES (?, ?)")
                ->withParameters($input["name"], $input["category"])
                ->execute();

            $categoryIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE name = ?")
                ->withParameters($input["name"])
                ->getFirstRow();
            
            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], $categoryIdentifierRow["category"], $this->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function getRequiredArguments() {
            return array("name");
        }

        public function requiresAdminRole() {
            return FALSE;
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
    }
?>