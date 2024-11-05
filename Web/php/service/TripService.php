<?php
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../processor/GetYearIdentifierProcessor.php");

    class TripService {
        public function getTripIdentifier($name, $year) {
            global $databaseProvider, $highlightService;
            
            $tripIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE name = ? AND year " . $databaseProvider->getIsNullOrEqualTo($year))
                ->withParameters($name)
                ->getFirstRow();

            if ($tripIdentifierRow === NULL) {
                return NULL;
            }

            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                $highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
        }

        public function getTripIdentifierById($tripId) : ?TripIdentifier {
            global $databaseProvider, $highlightService;
            
            $tripIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                ->withParameters($tripId)
                ->getSingleRow();

            if ($tripIdentifierRow === NULL) {
                return NULL;
            }

            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                $highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
        }

        public function updateMainHighlight($tripId, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE trip_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $tripId)
                ->execute() === 1;
        }
        
        public function getOrCreateTripIdentifier($name, $year) { 
            global $databaseProvider;

            $tripIdentifier = $this->getTripIdentifier($name, $year);
            if ($tripIdentifier !== NULL) {
                return $tripIdentifier;
            }
            
            // Make sure the year is registered so it can be used as a foreign key.
            if ($year !== NULL) {
                (new GetYearIdentifierProcessor())
                    ->process(array(
                        "year" => $year));
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO trip_identifier (name, year) VALUES (?, ?)")
                ->withParameters($name, $year)
                ->execute();
                
            return $this->getTripIdentifier($name, $year);
        }
    }
?>