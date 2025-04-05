<?php
    require_once(dirname(__FILE__) . "/StatisticsMapper.php");
    require_once(dirname(__FILE__) . "/../model/Statistics.php");
    require_once(dirname(__FILE__) . "/../model/KeyValuePair.php");

    class StatisticsService {
        
        private const UPDATE_OVERALL_STATISTICS_ACTION_NAME = "UPDATE_OVERALL_STATISTICS";
        private const UPDATE_OVERALL_STATISTICS_ACTION_INTERVAL = 604800;

        private const STATISTICS_VALUES_COUNT_LIMIT = 5;

        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1/1/%s";
        private const END_OF_YEAR_DATE_FORMAT = "31/12/%s";

        private readonly StatisticsMapper $statisticsMapper;

        private readonly ConfigurationService $configurationService;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private array $statisticsProviders = array();

        public function __construct(DatabaseProvider $databaseProvider, ConfigurationService $configurationService,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->statisticsMapper = new StatisticsMapper($databaseProvider);
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
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
                throw new InvalidArgumentException("Unable to update statistics for the '" . $trip->getName() . " " . $trip->getYear() . "' trip.");
            }  

            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), $trip->getEnd(), NULL, $trip->getId());
            $this->eventPublisher->publishTripStatisticsUpdatedEvent($trip->getId(), $trip->getYear());
        }

        public function updateOverallStatistics() : void {     
            $this->updateStatistics(StatisticsType::Overall, 0, time(), NULL, NULL);          
        }

        public function setStatisticsProviders(array $statisticsProviders) : void {
            $this->statisticsProviders = array($this);
            // TODO: Uncomment. Remove above.
            // $this->statisticsProviders = $statisticsProviders;
        }

        // TODO: Remove. Move to individual services (statistics providers).
        private function fetchStatistics(StatisticsType $statisticsType, int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statisticsRecords = array();

            // Compute fact statistics.
            foreach ($this->computeStatistics($statisticsType, StatisticsKind::Fact, $start, $end, $categoryId) as &$fact) {
                foreach ($fact["computedRows"] as &$computedRow) {
                    $statisticsRecords[] = new Statistics($fact["name"], $computedRow[array_key_first($computedRow)], $fact["unit"]);
                }
            }

            // Compute standings statistics.
            foreach ($this->computeStatistics($statisticsType, StatisticsKind::Standings, $start, $end, $categoryId) as &$standings) {
                $keyValuePairs = array();
                foreach ($standings["computedRows"] as &$computedRow) {
                    $keyValuePairs[] = new KeyValuePair($computedRow[array_key_first($computedRow)], $computedRow[array_key_last($computedRow)]);
                }
                $statisticsRecords[] = new Statistics($standings["name"], $keyValuePairs, $standings["unit"]);
            }

            return $statisticsRecords;
        }    

        // TODO: Remove.
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

            foreach ($this->statisticsProviders as &$statisticsProvider) {
                $fetchedStatisticsRecords = $statisticsProvider->fetchStatistics($statisticsType, $start, $end, $categoryId, $entityId);
                foreach ($fetchedStatisticsRecords as &$fetchedStatisticsRecord) {
                    if ($fetchedStatisticsRecord->hasValue()) {
                        $this->statisticsMapper->insertStatisticsRecord($statisticsType,
                            $fetchedStatisticsRecord->withLimitedValuesCount(self::STATISTICS_VALUES_COUNT_LIMIT), $entityId);
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
                throw new InvalidArgumentException("The entity identifier is required.");
            }
            
            return $this->statisticsMapper->selectStatisticsRecords($statisticsType, $entityId);
        }

        public function onCategoryUpdated(mixed $message) : void {
            // TODO: Introduce the CategoryService $categoryService field after moving this method to a new listener class.
            global $categoryService;

            $categoryIdentifier = $categoryService->getCategoryIdentifierById($message["categoryId"]);
            if ($categoryIdentifier !== NULL) {
                $this->updateCategoryStatistics($categoryIdentifier);
            }
        }

        public function onCategoryStatisticsInvalidated(mixed $message) : void {
            // TODO: Introduce the CategoryService $categoryService field after moving this method to a new listener class.
            global $categoryService;

            $categoryIdentifier = $categoryService->getCategoryIdentifierById($message["categoryId"]);
            if ($categoryIdentifier !== NULL) {
                $this->updateCategoryStatistics($categoryIdentifier);
            }
        }

        public function onExpenseCreated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onExpenseUpdated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onExpenseRemoved(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onFitnessDataUpdated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trips = $tripService->getTripsContainingInterval($message["start"], $message["end"]);
            foreach ($trips as &$trip) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onFlightLogged(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onFlightEventCreated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onFlightEventUpdated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onFlightEventDeleted(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onStayEventCreated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onStayEventUpdated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onStayEventDeleted(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onYearStatisticsUpdated(mixed $message) : void {
            $this->updateOverallStatistics();
        }

        public function onCategoryStatisticsUpdated(mixed $message) : void {
            $this->updateOverallStatistics();
        }

        public function onTripStatisticsUpdated(mixed $message) : void {
            $this->updateYearStatistics($message["year"]);
        }

        public function onOverallStatisticsInvalidated(mixed $message) : void {
            $this->updateOverallStatistics();
        }

        public function onYearStatisticsInvalidated(mixed $message) : void {
            $this->updateYearStatistics($message["year"]);
        }

        public function onTripStatisticsInvalidated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            $trip = $tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->updateTripStatistics($trip);
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::UPDATE_OVERALL_STATISTICS_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::UPDATE_OVERALL_STATISTICS_ACTION_INTERVAL) {
                $this->eventPublisher->publishOverallStatisticsInvalidatedEvent();                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_OVERALL_STATISTICS_ACTION_NAME);
            }
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