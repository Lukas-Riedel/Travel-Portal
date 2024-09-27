<?php
    require_once(dirname(__FILE__) . "/../model/Year.php");
    require_once(dirname(__FILE__) . "/GetStatsProcessor.php");

    class GetYearsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["year"])) {
                $whereClauseBuilder->withClause("year = ?", $input["year"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $years = $databaseProvider
                ->statementBuilder("SELECT DISTINCT year FROM trip_summary {{WHERE CLAUSE}}", $whereClause)
                ->getResultSetForColumn("year");

            $result = array();

            foreach ($years as &$year) {
                $stats = array();

                $includeStats = isset($input["includeStats"]) && $input["includeStats"] == "true";
                if ($includeStats || isset($input["year"])) {
                    $stats = $this->getStats($year);                      
                }

                $result[] = new Year($year, $stats);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
        
        private function getStats($year) {
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "year", 
                    "id" => $year));
        }
    }
?>