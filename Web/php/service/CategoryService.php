<?php
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");

    class CategoryService {
        public function getCategoryIdentifier($name) {
            global $databaseProvider, $highlightService;
            
            $categoryIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE name = ?")
                ->withParameters($name)
                ->getFirstRow();

            if ($categoryIdentifierRow === NULL) {
                return NULL;
            }

            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], 
                $categoryIdentifierRow["category"], $highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }
        
        public function getOrCreateCategoryIdentifier($name, $category) { 
            global $databaseProvider;

            $categoryIdentifier = $this->getCategoryIdentifier($name);
            if ($categoryIdentifier !== NULL) {
                return $categoryIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO category_identifier (name, category) VALUES (?, ?)")
                ->withParameters($name, $category)
                ->execute();
                
            return $this->getCategoryIdentifier($name);
        }
    }
?>