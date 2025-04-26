<?php
    namespace Service\Service\Statistics;

    use Service\Service\Category\CategoryIdentifier;
    use Service\Service\Trip\Trip;

    class StatisticsService {

        private const STATISTICS_VALUES_COUNT_LIMIT = 5;

        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1/1/%s 12:00:00 AM";
        private const END_OF_YEAR_DATE_FORMAT = "12/31/%s 11:59:59 PM";

        private readonly StatisticsMapper $statisticsMapper;

        private readonly \ConfigurationService $configurationService;
        
        private readonly \EventPublisher $eventPublisher;

        private array $statisticsProviders = array();

        public function __construct(\DatabaseProvider $databaseProvider, \ConfigurationService $configurationService, \EventPublisher $eventPublisher) {
            $this->statisticsMapper = new StatisticsMapper($databaseProvider);
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
        }

        public function getCategoryStatistics(string $categoryId) : array {    
            return $this->getStatistics(StatisticsType::Category, $categoryId);
        }
        
        public function getYearStatistics(int $year) : array {     
            return $this->getStatistics(StatisticsType::Year, $year);
        }
        
        public function getTripStatistics(string $tripId) : array {         
            return $this->getStatistics(StatisticsType::Trip, $tripId);
        }

        public function getOverallStatistics() : array {     
            return $this->getStatistics(StatisticsType::Overall, NULL);          
        }
        
        public function updateCategoryStatistics(CategoryIdentifier $categoryIdentifier) : void {    
            if ($this->isVariableTimeCategory($categoryIdentifier)) {
                $variableTimeCategoryInterval = $this->configurationService->getConfigurationEntry("variableTimeCategories", $categoryIdentifier->getName());
                $this->updateStatistics(StatisticsType::Category, time() - $variableTimeCategoryInterval, time(), 
                    $categoryIdentifier->getId(), $categoryIdentifier->getId());
            }
            else {
                $this->updateStatistics(StatisticsType::Category, 0, time(), $categoryIdentifier->getId(), $categoryIdentifier->getId());
            }
            $this->eventPublisher->publishCategoryStatisticsUpdatedEvent($categoryIdentifier->getId());
        }
        
        public function updateYearStatistics(int $year) : void {
            $this->updateStatistics(StatisticsType::Year, $this->getBeginningOfYearTimestamp($year), $this->getEndOfYearTimestamp($year), NULL, $year);
            $this->eventPublisher->publishYearStatisticsUpdatedEvent($year);
        }

        public function updateTripStatistics(Trip $trip) : void {            
            if ($this->isSpecialTrip($trip)) {
                return;
            }  

            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), $trip->getEnd(), NULL, $trip->getId());
            $this->eventPublisher->publishTripStatisticsUpdatedEvent($trip->getId(), $trip->getYear());
        }

        public function updateOverallStatistics() : void {     
            $this->updateStatistics(StatisticsType::Overall, 0, time(), NULL, NULL);          
        }

        public function setStatisticsProviders(array $statisticsProviders) : void {
            $this->statisticsProviders = $statisticsProviders;
            // TODO: Remove after statistics rework.
            $this->statisticsProviders[] = $this;
        }

        // TODO: Remove after statistics rework. Move to individual services (statistics providers).
        private function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statisticsRecords = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                foreach ($this->computeStatistics($statisticsType, StatisticsKind::Fact, $start, $end, $categoryId) as &$fact) {
                    foreach ($fact["computedRows"] as &$computedRow) {
                        if ($computedRow[array_key_first($computedRow)] != NULL) {
                            $statisticsRecords[] = new Statistics($fact["name"], $computedRow[array_key_first($computedRow)], StatisticsUnit::from($fact["unit"]));
                        }
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {
                foreach ($this->computeStatistics($statisticsType, StatisticsKind::Standings, $start, $end, $categoryId) as &$standings) {
                    $keyValuePairs = array();
                    foreach ($standings["computedRows"] as &$computedRow) {
                        if ($computedRow[array_key_first($computedRow)] != NULL && $computedRow[array_key_last($computedRow)] != NULL) {
                            $keyValuePairs[] = new KeyValuePair($computedRow[array_key_first($computedRow)], $computedRow[array_key_last($computedRow)]);
                        }
                    }
                    $statisticsRecords[] = new Statistics($standings["name"], $keyValuePairs, StatisticsUnit::from($standings["unit"]));
                }
            }

            return $statisticsRecords;
        }    

            // TODO: Remove after statistics rework.
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

        private function updateStatistics(StatisticsType $statisticsType, int $start, int $end, ?string $categoryId, ?string $entityId) : void {
            $this->statisticsMapper->deleteAllStatisticsRecords($statisticsType, $entityId);

            foreach (StatisticsKind::cases() as &$statisticsKind) {
                foreach ($this->statisticsProviders as &$statisticsProvider) {
                    $fetchedStatisticsRecords = $statisticsProvider->fetchStatistics($statisticsType, $statisticsKind, $start, $end, $categoryId, $entityId);
                    foreach ($fetchedStatisticsRecords as &$fetchedStatisticsRecord) {
                        if ($fetchedStatisticsRecord->hasValue()) {
                            $this->statisticsMapper->insertStatisticsRecord($statisticsType,
                                $fetchedStatisticsRecord->withLimitedValuesCount(self::STATISTICS_VALUES_COUNT_LIMIT), $entityId);
                        }
                    }
                }
            }
        }

        private function isVariableTimeCategory(CategoryIdentifier $categoryIdentifier) : bool {
            return in_array($categoryIdentifier->getName(), $this->configurationService->getConfigurationKeysForType("variableTimeCategories"));
        }

        private function isSpecialTrip(Trip $trip) : bool {
            return in_array($trip->getName(), $this->configurationService->getConfigurationValuesForType("specialTripNames"));
        }
        
        private function getBeginningOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::BEGINNING_OF_YEAR_DATE_FORMAT, $year));
        }
        
        private function getEndOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::END_OF_YEAR_DATE_FORMAT, $year));
        }

        private function getStatistics(StatisticsType $statisticsType, ?string $entityId) {
            if ($statisticsType !== StatisticsType::Overall && $entityId === NULL) {
                throw new \InvalidArgumentException("The entity identifier is required.");
            }
            
            return $this->statisticsMapper->selectStatisticsRecords($statisticsType, $entityId);
        }
    }
?>