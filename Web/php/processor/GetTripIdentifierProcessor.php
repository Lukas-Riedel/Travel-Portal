<?php 
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");

    class GetTripIdentifierProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $year = isset($input["year"]) ? intval($input["year"]) : NULL;
            
            $tripIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE name = ? AND year " . $databaseProvider->getIsNullOrEqualTo($year))
                ->withParameters($input["name"])
                ->getFirstRow();

            if ($tripIdentifierRow != NULL) {
                return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"]);
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO trip_identifier (name, year) VALUES (?, ?)")
                ->withParameters($input["name"], $year)
                ->execute();

            $tripIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE name = ? AND year " . $databaseProvider->getIsNullOrEqualTo($year))
                ->withParameters($input["name"])
                ->getFirstRow();
                
            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"]);
        }

        public function getRequiredArguments() {
            return array("name");
        }

        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>