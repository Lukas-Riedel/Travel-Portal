<?php
    require_once(dirname(__FILE__) . "/../model/YearIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Year.php");

    class YearService {
        public function getYear($year) : ?Year {
            global $statisticsService, $highlightService;

            $yearIdentifier = $this->getYearIdentifier($year);

            if ($yearIdentifier === NULL) {
                return NULL;
            }
                
            $stats = $statisticsService->getYearStatistics($year);   
            $highlights = $highlightService->getYearHighlights($year);   

            return new Year($year, $yearIdentifier->getMainHighlight(), $highlights, $stats);
        }

        public function getYears($includedEntities) : array {
            global $databaseProvider, $statisticsService, $highlightService;

            $years = array();

            $yearRows = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier")
                ->getResultSet();

            foreach ($yearRows as &$yearRow) {
                $stats = array();
                if (in_array(IncludedCategoryEntity::Statistics->value, $includedEntities)) {
                    $stats = $statisticsService->getYearStatistics($yearRow["id"]);               
                }

                $highlights = array();
                if (in_array(IncludedCategoryEntity::Highlights->value, $includedEntities)) {
                    $highlights = $highlightService->getYearHighlights($yearRow["id"]);                      
                }

                $years[] = new Year($yearRow["id"], $highlightService->getHighlight($yearRow["main_highlight_id"]), $highlights, $stats);
            }

            return $years;
        }

        public function getYearIdentifier($year) : ?YearIdentifier {
            global $databaseProvider, $highlightService;
            
            $yearIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier WHERE id = ?")
                ->withParameters($year)
                ->getSingleRow();

            if ($yearIdentifierRow === NULL) {
                return NULL;
            }
            
            return new YearIdentifier($yearIdentifierRow["id"], $highlightService->getHighlight($yearIdentifierRow["main_highlight_id"]));
        }

        public function getOrCreateYearIdentifier($year) : YearIdentifier {            
            global $databaseProvider;

            $yearIdentifier = $this->getYearIdentifier($year);
            if ($yearIdentifier !== NULL) {
                return $yearIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO year_identifier (id) VALUES (?)")
                ->withParameters($year)
                ->execute();
                
            return $this->getYearIdentifier($year);
        }

        public function updateYearMainHighlight($year, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE year_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $year)
                ->execute() === 1;
        }
    }

    enum IncludedYearEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";
    }
?>