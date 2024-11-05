<?php
    require_once(dirname(__FILE__) . "/../model/YearIdentifier.php");

    class YearService {
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

        public function updateMainHighlight($year, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE year_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $year)
                ->execute() === 1;
        }
    }
?>