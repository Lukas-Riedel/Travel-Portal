<?php
    require_once(dirname(__FILE__) . "/../model/Trip.php");
    require_once(dirname(__FILE__) . "/../model/Note.php");
    require_once(dirname(__FILE__) . "/GetPublicHolidaysProcessor.php");

    class GetCandidateTripsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["tripId"])) {
                $whereClauseBuilder->withClause("ti.id = ?", $input["tripId"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $tripRows = $databaseProvider
                ->statementBuilder("SELECT ti.id, ti.name, tc.days, tc.countries FROM (SELECT trip_id, CEIL(MAX(end) / 86400) AS days, GROUP_CONCAT(DISTINCT pi.country SEPARATOR ',') AS countries FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id GROUP BY pce.trip_id) tc INNER JOIN trip_identifier ti ON tc.trip_id = ti.id {{WHERE CLAUSE}} ORDER BY ti.name", $whereClause)
                ->getResultSet();

            $result = array();

            foreach ($tripRows as &$tripRow) {
                $notes = array();
                $publicHolidays = array();
                
                if (isset($input["tripId"])) {
                    $notes = $this->getNotes($tripRow["id"]);
                    $publicHolidays = $this->getPublicHolidays($tripRow["id"], explode(",", $tripRow["countries"]));
                }

                $result[] = new Trip($tripRow["id"], $tripRow["name"], NULL, NULL, NULL, NULL, explode(",", $tripRow["countries"]), NULL, 
                    $tripRow["days"], NULL, NULL, NULL, array(), array(), array(), array(), array(), array(), array(), $notes, array(), $publicHolidays);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getNotes($tripId) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id, content FROM note WHERE trip_id = ?")
                ->withParameters($tripId)
                ->getMappedResultSet(function ($noteRow) {
                    return new Note($noteRow["id"], $noteRow["content"]);
                });
        }
    
        private function getPublicHolidays($tripId, $countries) {
            global $databaseProvider;

            $getPublicHolidaysProcessor = new GetPublicHolidaysProcessor();

            $tempHolidays = array();

            foreach ($countries as &$country) {
                $holidays = $getPublicHolidaysProcessor
                    ->process(array(
                        "country" => $country));

                foreach ($holidays as &$holiday) {
                    $tempHolidays[strtotime($holiday->getDate())] = $holiday;
                }
            }

            ksort($tempHolidays);

            return array_values($tempHolidays);
        }
    }
?>