<?php
    namespace Core\Service\Year;

    use Core\Service\Highlight\HighlightService;
    use Core\Service\Statistics\StatisticsService;
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;
    use Core\Service\Fitness\FitnessService;

    class YearMapper {

        private readonly DatabaseClient $databaseClient;
        private readonly FitnessService $fitnessService;
        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(DatabaseClient $databaseClient, FitnessService $fitnessService, HighlightService $highlightService, StatisticsService $statisticsService) {
            $this->databaseClient = $databaseClient;
            $this->fitnessService = $fitnessService;
            $this->highlightService = $highlightService;
            $this->statisticsService = $statisticsService;
        }

        public function selectYearIdentifier(int $year) : ?YearIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM year_identifier
                WHERE id = ?
            SQL;

            $yearIdentifierRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($year)
                ->getSingleRow();

            if ($yearIdentifierRow === null) {
                return null;
            }

            return new YearIdentifier($yearIdentifierRow["id"], $this->highlightService->getHighlight($yearIdentifierRow["main_highlight_id"]));
        }

        public function selectYears(?int $year, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM year_identifier
                WHERE :CONDITIONS
                ORDER BY id DESC
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($year !== null) {
                $whereClauseBuilder->withClause("id = ?", $year);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($yearRow) use(&$includedEntities) {
                    $fitness = array();
                    if (in_array(YearIncludedEntity::Fitness->value, $includedEntities)) {
                        $startOfDay = mktime(0, 0, 0, 1, 1, $yearRow["id"]);
                        $endOfYear = mktime(0, 0, 0, 1, 1, $yearRow["id"] + 1);

                        while ($startOfDay < $endOfYear) {
                            $fitness[] = $this->fitnessService->getFitnessRecordForInterval($startOfDay, $startOfDay + CommonConstants::ONE_DAY_SECONDS);
                            $startOfDay += CommonConstants::ONE_DAY_SECONDS;
                        }
                    }

                    $highlights = array();
                    if (in_array(YearIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getYearHighlights($yearRow["id"]);                      
                    }
    
                    $statistics = array();
                    if (in_array(YearIncludedEntity::Statistics->value, $includedEntities)) {
                        $statistics = $this->statisticsService->getYearStatistics($yearRow["id"]);              
                    }

                    return new Year($yearRow["id"], $this->highlightService->getHighlight($yearRow["main_highlight_id"]), $fitness, $highlights, $statistics);
                });
        }

        public function insertYearIdentifier(YearIdentifier $yearIdentifier) : bool {
            $sql = <<<'SQL'
                INSERT INTO year_identifier (
                    id
                )
                VALUES (
                    ?
                )
                ON CONFLICT DO NOTHING
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($yearIdentifier->getId())
                ->execute() === 1;
        }

        public function updateYearMainHighlight(int $year, ?string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE year_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $year)
                ->execute() === 1;
        }
    }
?>