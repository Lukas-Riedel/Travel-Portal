<?php
    require_once(dirname(__FILE__) . "/YearMapper.php");
    require_once(dirname(__FILE__) . "/../Model/YearIdentifier.php");
    require_once(dirname(__FILE__) . "/../Model/Year.php");

    class YearService {
        
        private const UPDATE_YEAR_STATISTICS_ACTION_NAME = "UPDATE_YEAR_STATISTICS";
        private const UPDATE_YEAR_STATISTICS_ACTION_INTERVAL = 604800;
        
        private readonly YearMapper $yearMapper;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, HighlightService $highlightService,
            StatisticsService $statisticsService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->yearMapper = new YearMapper($databaseProvider, $highlightService, $statisticsService);
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function getYear(int $year) : ?Year {
            $years = $this->yearMapper->selectYears($year, YearIncludedEntity::values());
            return count($years) === 1 ? $years[0] : NULL;
        }

        public function getYears(array $includedEntities) : array {
            return $this->yearMapper->selectYears(NULL, $includedEntities);
        }

        public function getYearIdentifier(int $year) : ?YearIdentifier {
            return $this->yearMapper->selectYearIdentifier($year);
        }

        public function getOrCreateYearIdentifier(int $year) : YearIdentifier {
            $yearIdentifier = $this->getYearIdentifier($year);
            if ($yearIdentifier !== NULL) {
                return $yearIdentifier;
            }

            $yearIdentifier = new YearIdentifier($year, NULL);
            $this->yearMapper->insertYearIdentifier($yearIdentifier);

            return $yearIdentifier;
        }

        public function updateYearMainHighlight(int $year, string $highlightIdentifier) : bool {
            return $this->yearMapper->updateYearMainHighlight($year, $highlightIdentifier);
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->name) {
                $yearIdentifier = $this->getYearIdentifier($message["entityId"]);
                if ($yearIdentifier !== NULL && $yearIdentifier->getMainHighlight() === NULL) {
                    $this->updateYearMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {            
            if ($message["action"] === self::UPDATE_YEAR_STATISTICS_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::UPDATE_YEAR_STATISTICS_ACTION_INTERVAL) {
                $years = $this->getYears(array());
                foreach ($years as &$year) {
                    $this->eventPublisher->publishYearStatisticsInvalidatedEvent($year->getId());
                }                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_YEAR_STATISTICS_ACTION_NAME);
            }
        }
    }

    enum YearIncludedEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>