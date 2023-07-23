<?php
    require_once(dirname(__FILE__) . "/GetCategoryIdentifierProcessor.php");

    class UpdateStatsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration, $schedulingProvider;
            
            $getCategoryIdentifierProcessor = new GetCategoryIdentifierProcessor();
            
            // Use default values if not specified otherwise.
            $start = 0;
            $end = time();
            $categoryId = -1;
            $type = strtoupper($input["type"]);

            if ($type != "ALL") {                
                if ($type == "YEAR") {
                    $start = strtotime("1 January " . $input["id"]);
                    $end = ($input["id"] >= date("Y")) ? time() : strtotime("31 December " . $input["id"]);    
                }
                else if ($type == "TRIP") {
                    $resolvedTripRow = $databaseProvider
                        ->statementBuilder("SELECT name, start, LEAST(end, UNIX_TIMESTAMP()) AS end, year FROM trip_summary WHERE trip_id = ?")
                        ->withParameters($input["id"])
                        ->getSingleRow();
    
                    // Do not cache stats for special trips.
                    if (in_array($resolvedTripRow["name"], $configuration["specialTripNames"])) {
                        return FALSE;
                    }
                    
                    $start = $resolvedTripRow["start"];
                    $end = $resolvedTripRow["end"];
                    
                    // Update the year of the trip.
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "YEAR", 
                            "id" => $resolvedTripRow["year"]), NULL);
                    
                    // Update variable categories if the trip end falls into the category timespan.
                    foreach ($configuration["variableTimeCategories"] as $categoryName => $categoryTimespan) {
                        if (time() - $end < $categoryTimespan) {
                            $categoryIdentifier = $getCategoryIdentifierProcessor
                                ->process(array(
                                    "name" => $categoryName,
                                    "category" => "VARIABLE"));
                            
                            $schedulingProvider
                                ->scheduleJobExecution("UpdateStats", array(
                                    "type" => "VARIABLE_TIME_CATEGORY", 
                                    "id" => $categoryIdentifier->getId()), NULL);
                        }
                    }
                }
                else if ($type == "VARIABLE_TIME_CATEGORY") {
                    $categoryName = $databaseProvider
                        ->statementBuilder("SELECT name FROM category_identifier WHERE id = ?")
                        ->withParameters($input["id"])
                        ->getSingleColumn("name");
                    $start = time() - $configuration["variableTimeCategories"][$categoryName];
                    $end = time();
                    $type = "CATEGORY";
                }
                else if ($type == "CATEGORY") {
                    $categoryId = $input["id"];
                }
                else {
                    throw new InvalidArgumentException("Unable to update stats for type " . $type . ".");
                }
                
                // Update overall stats every time.
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "ALL"), NULL);
            }

            $stats = array();

            $factStatRows = $databaseProvider
                ->statementBuilder("SELECT name, query, unit FROM definition_statistics {{WHERE CLAUSE}} ORDER BY category", $this->getWhereClause($type, "FACT"))
                ->getResultSet();

            foreach ($factStatRows as &$factStatRow) {
                $name = $factStatRow["name"];
                $sql = $factStatRow["query"];
                $unit = $factStatRow["unit"];

                $sql = str_replace("{{start}}", $start, $sql);
                $sql = str_replace("{{end}}", $end, $sql);
                $sql = str_replace("{{category}}", $databaseProvider->escape($categoryId), $sql);
                
                $computedStatRows = $databaseProvider
                    ->statementBuilder($sql)
                    ->getResultSet();

                foreach ($computedStatRows as &$computedStatRow) {
                    if ($computedStatRow[array_key_first($computedStatRow)] != NULL) {
                        $stats[] = array(
                            "name" => $name, 
                            "value" => $this->convert($computedStatRow[array_key_first($computedStatRow)]), 
                            "unit" => $unit);
                    }
                }    
            }

            $standingStatRows = $databaseProvider
                ->statementBuilder("SELECT name, query, unit FROM definition_statistics {{WHERE CLAUSE}} ORDER BY category", $this->getWhereClause($type, "STANDINGS"))
                ->getResultSet();

            foreach ($standingStatRows as &$standingStatRow) {
                $name = $standingStatRow["name"];
                $sql = $standingStatRow["query"];
                $unit = $standingStatRow["unit"];

                $sql = str_replace("{{start}}", $start, $sql);
                $sql = str_replace("{{end}}", $end, $sql);
                $sql = str_replace("{{category}}", $databaseProvider->escape($categoryId), $sql);
                
                $standings = array();
                $i = 0;

                $computedStatRows = $databaseProvider
                    ->statementBuilder($sql)
                    ->getResultSet();

                foreach ($computedStatRows as &$computedStatRow) {
                    $standings[] = array(
                        "key" => $computedStatRow[array_key_first($computedStatRow)], 
                        "value" => $this->convert($computedStatRow[array_key_last($computedStatRow)]));

                    if (++$i >= $configuration["standingsStatsLimit"]) {
                        break;
                    }
                }        

                if (!empty($standings)) {
                    $stats[] = array(
                        "name" => $name, 
                        "value" => $standings,
                        "unit" => $unit);
                }
            }

            $table = $this->resolveTable($type);

            if ($type == "ALL") {
                $databaseProvider
                    ->statementBuilder("DELETE FROM " . $table)
                    ->execute();
    
                foreach ($stats as &$stat) {
                    $databaseProvider
                        ->statementBuilder("INSERT INTO " . $table . " (last_update, name, value, unit) VALUES (UNIX_TIMESTAMP(), ?, ?, ?)")
                        ->withParameters($stat["name"], json_encode($stat["value"], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG), $stat["unit"])
                        ->execute();
                }
            }
            else {
                $databaseProvider
                    ->statementBuilder("DELETE FROM " . $table . " WHERE id = ?")
                    ->withParameters($input["id"])
                    ->execute();
                
                foreach ($stats as &$stat) {
                    $databaseProvider
                        ->statementBuilder("INSERT INTO " . $table . " (id, last_update, name, value, unit) VALUES (?, UNIX_TIMESTAMP(), ?, ?, ?)")
                        ->withParameters($input["id"], $stat["name"], json_encode($stat["value"], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG), $stat["unit"])
                        ->execute();
                }
            }

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("type");
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getWhereClause($statsType, $statsKind) {
            global $databaseProvider;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder()->withClause("kind = ?", $statsKind);
            if ($statsType != "ALL") {
                $whereClauseBuilder->withClause("(FIND_IN_SET(?, types) <> 0)", $statsType);
            }
            return $whereClauseBuilder->buildForAnd();
        }

        private function resolveTable($type) {
            if ($type == "ALL") {
                return "cache_statistics_all";
            }
            if ($type == "TRIP") {
                return "cache_statistics_trip";
            }
            if ($type == "CATEGORY") {
                return "cache_statistics_category";
            }
            if ($type == "YEAR") {
                return "cache_statistics_year";
            }
            throw new InvalidArgumentException("Unknown statistics type " . $type . ". Permitted values: all, trip, category, year");
        }

        private function convert($value) {
            return is_numeric($value) ? floatval($value) : $value;
        }
    }
?>