<?php
    require_once(dirname(__FILE__) . "/UpdateStatsProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Statistics.php");

    class GetStatsProcessor extends Processor {        
        public function process($input) {
            $stats = $this->getStats($input);
            if ($stats != NULL) {
                return $stats;
            }
            
            (new UpdateStatsProcessor())
                ->process($input);

            return $this->getStats($input);
        }

        public function getRequiredArguments() {
            return array("type");
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getStats($input) {
            global $databaseProvider;
            
            $table = $this->resolveTable($input["type"]);
            if ($input["type"] != "all" && !isset($input["id"])) {
                throw new InvalidArgumentException("The argument 'id' is required.");
            }
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["id"])) {
                $whereClauseBuilder->withClause("id = ?", $input["id"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $databaseProvider
                ->statementBuilder("SELECT name, value, unit FROM " . $table . " {{WHERE CLAUSE}}", $whereClause)
                ->getMappedResultSet(function ($statRow) {
                    return new Statistics($statRow["name"], json_decode($statRow["value"], TRUE), $statRow["unit"]);
                });
        }

        private function resolveTable($type) {
            if ($type == "all") {
                return "cache_statistics_all";
            }
            if ($type == "trip") {
                return "cache_statistics_trip";
            }
            if ($type == "category") {
                return "cache_statistics_category";
            }
            if ($type == "year") {
                return "cache_statistics_year";
            }
            throw new InvalidArgumentException("Unknown statistics type " . $type . ". Permitted values: all, trip, category, year");
        }
    }
?>