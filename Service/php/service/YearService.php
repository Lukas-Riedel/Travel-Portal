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
?>