<?php
    namespace Core\Service\Year;

    use Core\Service\Highlight\HighlightService;
    use Core\Service\Statistics\StatisticsService;
    
    class YearMapper {

        private readonly \DatabaseProvider $databaseProvider;

        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(\DatabaseProvider $databaseProvider, HighlightService $highlightService, StatisticsService $statisticsService) {
            $this->databaseProvider = $databaseProvider;
            $this->highlightService = $highlightService;
            $this->statisticsService = $statisticsService;
        }

        public function selectYearIdentifier(int $year) : ?YearIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM year_identifier
                WHERE id = ?
            SQL;

            $yearIdentifierRow = $this->databaseProvider
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

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($year !== null) {
                $whereClauseBuilder->withClause("id = ?", $year);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($yearRow) use(&$includedEntities) {
                    $highlights = array();
                    if (in_array(YearIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getYearHighlights($yearRow["id"]);                      
                    }
    
                    $statistics = array();
                    if (in_array(YearIncludedEntity::Statistics->value, $includedEntities)) {
                        $statistics = $this->statisticsService->getYearStatistics($yearRow["id"]);              
                    }

                    return new Year($yearRow["id"], $this->highlightService->getHighlight($yearRow["main_highlight_id"]), $highlights, $statistics);
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
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($yearIdentifier->getId())
                ->execute() === 1;

            if ($yearIdentifier) {
                $yearIdentifier->setId($this->databaseProvider->getLastInsertedId());
            }
            
            return $wasInserted;
        }

        public function updateYearMainHighlight(int $year, string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE year_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $year)
                ->execute() === 1;
        }
    }
?>