<?php
    require_once(dirname(__FILE__) . "/../model/Category.php");
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
                $stats = array();

                $includeStats = isset($input["includeStats"]) && $input["includeStats"] == "true";
                if ($includeStats || isset($input["categoryId"])) {
                    $stats = $this->getStats($categoryRow);                      
                }

                $result[] = new Category($categoryRow["id"], $categoryRow["name"], $categoryRow["category"], $stats);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
        
        private function getStats($categoryRow) {
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "category", 
                    "id" => $categoryRow["id"]));
        }
    }
?>