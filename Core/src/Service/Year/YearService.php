<?php
    namespace Core\Service\Year;
    
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Statistics\StatisticsService;
    use Core\Client\Database\DatabaseClient;
use Core\Service\Fitness\FitnessService;

    class YearService {
        
        private readonly YearMapper $yearMapper;

        public function __construct(DatabaseClient $databaseClient, FitnessService $fitnessService,
            HighlightService $highlightService, StatisticsService $statisticsService) {
            $this->yearMapper = new YearMapper($databaseClient, $fitnessService, $highlightService, $statisticsService);
        }

        public function getYear(int $year) : ?Year {
            $years = $this->yearMapper->selectYears($year, YearIncludedEntity::values());
            return count($years) === 1 ? $years[0] : null;
        }

        public function getYears(array $includedEntities) : array {
            return $this->yearMapper->selectYears(null, $includedEntities);
        }

        public function getYearIdentifier(int $year) : ?YearIdentifier {
            return $this->yearMapper->selectYearIdentifier($year);
        }

        public function getOrCreateYearIdentifier(int $year) : YearIdentifier {
            $yearIdentifier = $this->getYearIdentifier($year);
            if ($yearIdentifier !== null) {
                return $yearIdentifier;
            }

            $yearIdentifier = new YearIdentifier($year, null);
            $this->yearMapper->insertYearIdentifier($yearIdentifier);

            return $yearIdentifier;
        }

        public function updateYearMainHighlight(int $year, ?string $highlightIdentifier) : bool {
            return $this->yearMapper->updateYearMainHighlight($year, $highlightIdentifier);
        }
    }
?>