<?php 
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");

    class GetCategoryIdentifierProcessor extends Processor {  
        public function process($input) {
            global $configuration, $databaseProvider;
            
            $categoryIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE name = ?")
                ->withParameters($input["name"])
                ->getFirstRow();

            if ($categoryIdentifierRow != NULL) {
                return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], $categoryIdentifierRow["category"]);
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
            
            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], $categoryIdentifierRow["category"]);
        }

        public function getRequiredArguments() {
            return array("name");
        }

        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>