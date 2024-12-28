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

        private function getStatistics($statisticsType, $entityId) {
            global $databaseProvider;
            
            $table = $this->resolveStatisticsTable($statisticsType);
            if ($statisticsType !== StatisticsType::Overall && $entityId === NULL) {
                throw new InvalidArgumentException("The argument 'id' is required.");
            }
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($entityId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $entityId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $databaseProvider
                ->statementBuilder("SELECT name, value, unit FROM " . $table . " {{WHERE CLAUSE}}", $whereClause)
                ->getMappedResultSet(function ($statisticsRow) {
                    return new Statistics($statisticsRow["name"], json_decode($statisticsRow["value"], TRUE), $statisticsRow["unit"]);
                });
        }

        private function resolveStatisticsTable($statisticsType) {
            if ($statisticsType === StatisticsType::Overall) {
                return "cache_statistics_all";
            }
            if ($statisticsType === StatisticsType::Trip) {
                return "cache_statistics_trip";
            }
            if ($statisticsType === StatisticsType::Category) {
                return "cache_statistics_category";
            }
            if ($statisticsType === StatisticsType::Year) {
                return "cache_statistics_year";
            }
            throw new InvalidArgumentException("Unknown statistics type " . $statisticsType . ".");
        }
    }
        
    enum StatisticsType {
        case Overall;
        case Trip;
        case Category;
        case Year;
    }
?>