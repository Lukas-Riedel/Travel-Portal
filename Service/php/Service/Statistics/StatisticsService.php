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
            $this->updateStatistics(StatisticsType::Category, 0, time(), $categoryIdentifier->getId(), $categoryIdentifier->getId());
            $this->eventPublisher->publishCategoryStatisticsUpdatedEvent($categoryIdentifier->getId());
        }
        
        public function updateYearStatistics(int $year) : void {
            $this->updateStatistics(StatisticsType::Year, $this->getBeginningOfYearTimestamp($year), min(time(), $this->getEndOfYearTimestamp($year)), NULL, $year);
            $this->eventPublisher->publishYearStatisticsUpdatedEvent($year);
        }

        public function updateTripStatistics(Trip $trip) : void {            
            if ($this->isSpecialTrip($trip)) {
                return;
            }  

            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), min(time(), $trip->getEnd()), NULL, $trip->getId());
            $this->eventPublisher->publishTripStatisticsUpdatedEvent($trip->getId(), $trip->getYear());
        }

        public function updateOverallStatistics() : void {     
            $this->updateStatistics(StatisticsType::Overall, 0, time(), NULL, NULL);          
        }

        public function setStatisticsProviders(array $statisticsProviders) : void {
            $this->statisticsProviders = $statisticsProviders;
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