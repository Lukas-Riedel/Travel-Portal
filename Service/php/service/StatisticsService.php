<?php
    require_once(dirname(__FILE__) . "/../processor/UpdateStatsProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Statistics.php");

    class StatisticsService {
        public function getCategoryStatistics($categoryId) : array {    
            return $this->getStatistics(StatisticsType::Category, $categoryId);
        }
        
        public function getYearStatistics($year) : array {     
            return $this->getStatistics(StatisticsType::Year, $year);
        }
        
        public function getTripStatistics($tripId) : array {         
            return $this->getStatistics(StatisticsType::Trip, $tripId);
        }

        public function getOverallStatistics() : array {     
            return $this->getStatistics(StatisticsType::Overall, NULL);          
        }
        
        public function updateCategoryStatistics($categoryIdentifier) : void {    
            global $configuration;

            if (array_key_exists($categoryIdentifier->getName(), $configuration["variableTimeCategories"])) {
                $this->doUpdateCategoryStatistics(time() - $configuration["variableTimeCategories"][$categoryIdentifier->getName()], time(), $categoryIdentifier->getId());
            }
            else {
                $this->doUpdateCategoryStatistics(0, time(), $categoryIdentifier->getId());
            }
        }

        private function doUpdateCategoryStatistics($start, $end, $categoryId) : void {
            global $schedulingProvider;

            $this->updateStatistics(StatisticsType::Category, $start, $end, $categoryId, $categoryId);

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => StatisticsType::Overall->value), NULL);
        }
        
        public function updateYearStatistics($year) : void {     
            global $schedulingProvider;

            $this->updateStatistics(StatisticsType::Year, strtotime("1.1." . $year), strtotime("31.12." . $year), NULL, $year);

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => StatisticsType::Overall->value), NULL);
        }
        
        public function updateTripStatistics($trip) : void {    
            global $schedulingProvider, $configuration;
            
            if (in_array($trip->getName(), $configuration["specialTripNames"])) {
                throw new InvalidArgumentException("Unable to update statistics for the '" . $trip->getName() . " " . $trip->getYear() . "' trip.");
            }  

            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), $trip->getEnd(), NULL, $trip->getId());

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => StatisticsType::Year->value, 
                    "id" => $trip->getYear()), NULL);

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => StatisticsType::Overall->value), NULL);
        }

        public function updateOverallStatistics() : void {     
            $this->updateStatistics(StatisticsType::Overall, 0, time(), NULL, NULL);          
        }

        private function updateStatistics($statisticsType, $start, $end, $categoryId, $entityId) : void {
            global $configuration, $databaseProvider;

            $statisticsRecords = array();

            // Compute fact statistics.
            foreach ($this->computeStatistics($statisticsType, StatisticsKind::Fact, $start, $end, $categoryId) as &$fact) {
                foreach ($fact["computedRows"] as &$computedRow) {
                    if ($computedRow[array_key_first($computedRow)] !== NULL) {
                        $statisticsRecords[] = array(
                            "name" => $fact["name"], 
                            "value" => $this->convert($computedRow[array_key_first($computedRow)]), 
                            "unit" => $fact["unit"]
                        );
                    }
                }
            }

            // Compute standings statistics.
            foreach ($this->computeStatistics($statisticsType, StatisticsKind::Standings, $start, $end, $categoryId) as &$standings) {
                $standingsStatistics = array();
                $i = 0;

                foreach ($standings["computedRows"] as &$computedRow) {
                    $standingsStatistics[] = array(
                        "key" => $computedRow[array_key_first($computedRow)], 
                        "value" => $this->convert($computedRow[array_key_last($computedRow)]));

                    if (++$i >= $configuration["standingsStatsLimit"]) {
                        break;
                    }
                }

                if (!empty($standingsStatistics)) {
                    $statisticsRecords[] = array(
                        "name" => $standings["name"], 
                        "value" => $standingsStatistics,
                        "unit" => $standings["unit"]
                    );
                }
            }

            // Persist statistics.
            if ($entityId === NULL) {
                $databaseProvider
                    ->statementBuilder("DELETE FROM " . $statisticsType->getTableName())
                    ->execute();
    
                foreach ($statisticsRecords as &$statisticsRecord) {
                    $databaseProvider
                        ->statementBuilder("INSERT INTO " . $statisticsType->getTableName() . " (last_update, name, value, unit) VALUES (UNIX_TIMESTAMP(), ?, ?, ?)")
                        ->withParameters($statisticsRecord["name"], json_encode($statisticsRecord["value"], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG), $statisticsRecord["unit"])
                        ->execute();
                }
            }
            else {
                $databaseProvider
                    ->statementBuilder("DELETE FROM " . $statisticsType->getTableName() . " WHERE id = ?")
                    ->withParameters($entityId)
                    ->execute();
                
                foreach ($statisticsRecords as &$statisticsRecord) {
                    $databaseProvider
                        ->statementBuilder("INSERT INTO " . $statisticsType->getTableName() . " (id, last_update, name, value, unit) VALUES (?, UNIX_TIMESTAMP(), ?, ?, ?)")
                        ->withParameters($entityId, $statisticsRecord["name"], json_encode($statisticsRecord["value"], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG), $statisticsRecord["unit"])
                        ->execute();
                }
            }
        }        

        private function computeStatistics($statisticsType, $statisticsKind, $start, $end, $categoryId) : array {
            global $databaseProvider;       
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($statisticsType !== StatisticsType::Overall) {
                $whereClauseBuilder->withClause("(FIND_IN_SET(?, types) <> 0)", $statisticsType->value);
            }
            $whereClause = $whereClauseBuilder->withClause("kind = ?", $statisticsKind->value)->buildForAnd(); 

            return $databaseProvider
                ->statementBuilder("SELECT name, query, unit FROM definition_statistics {{WHERE CLAUSE}} ORDER BY category", $whereClause)
                ->getMappedResultSet(function($definitionRow) use(&$databaseProvider, $start, $end, $categoryId) {
                    $sql = $definitionRow["query"];    
                    $sql = str_replace("{{start}}", $databaseProvider->escape($start), $sql);
                    $sql = str_replace("{{end}}", $end > time() ? time() : $databaseProvider->escape($end), $sql);
                    $sql = str_replace("{{category}}", $categoryId === NULL ? -1 : $databaseProvider->escape($categoryId), $sql);

                    $computedRows = $databaseProvider
                        ->statementBuilder($sql)
                        ->getResultSet();

                    return array(
                        "name" => $definitionRow["name"],
                        "unit" => $definitionRow["unit"],
                        "computedRows" => $computedRows
                    );
                });
        }

        private function convert($value) {
            return is_numeric($value) ? floatval($value) : $value;
        }

        private function getStatistics($statisticsType, $entityId) {
            global $databaseProvider;
            
            if ($statisticsType !== StatisticsType::Overall && $entityId === NULL) {
                throw new InvalidArgumentException("The argument 'id' is required.");
            }
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($entityId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $entityId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $databaseProvider
                ->statementBuilder("SELECT name, value, unit FROM " . $statisticsType->getTableName() . " {{WHERE CLAUSE}}", $whereClause)
                ->getMappedResultSet(function ($statisticsRow) {
                    return new Statistics($statisticsRow["name"], json_decode($statisticsRow["value"], TRUE), $statisticsRow["unit"]);
                });
        }
    }
        
    enum StatisticsType : string {
        case Overall = "ALL";
        case Trip = "TRIP";
        case Category = "CATEGORY";
        case Year = "YEAR";

        public function getTableName() : string {
            return match ($this) {
                self::Overall => "cache_statistics_all",
                self::Trip => "cache_statistics_trip",
                self::Category => "cache_statistics_category",
                self::Year => "cache_statistics_year"
            };
        }
    }
    
    enum StatisticsKind : string {
        case Fact = "FACT";
        case Standings = "STANDINGS";
    }
?>