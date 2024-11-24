<?php
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Trip.php");
    require_once(dirname(__FILE__) . "/../processor/GetYearIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetTripsProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetCandidateTripsProcessor.php");

    class TripService {
        public function getRegularTrip($tripId) : ?Trip {
            $trips = (new GetTripsProcessor())
                ->process(array(
                    "tripId" => $tripId));
            return count($trips) === 1 ? $trips[0] : NULL;
        }

        public function getCandidateTrip($tripId) : ?Trip {
            $trips = (new GetCandidateTripsProcessor())
                ->process(array(
                    "tripId" => $tripId));
            return count($trips) === 1 ? $trips[0] : NULL;
        }

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

        public function updateTripMainHighlight($tripId, $highlightIdentifier) : bool {
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

        public function removeCandidateTrip($tripId) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("DELETE FROM place_candidate_event WHERE trip_id = ?")
                ->withParameters($tripId)
                ->execute();
        }

        public function archiveTrip($tripId) : Trip {            
            global $noteService, $placeService;

            $trip = $this->getRegularTrip($tripId);
            if ($trip === NULL) {
                throw new InvalidArgumentException("The trip " . $tripId . " could not be archived because it does not exist.");
            }
            
            $archivedTripIdentifier = $this->getOrCreateTripIdentifier($trip->getName(), NULL);
            $noteService->createNote($archivedTripIdentifier->getId(), date("j.n.Y", $trip->getStart()) . " - " . date("j.n.Y", $trip->getEnd()));

            $placeService->archivePlaces($tripId, $trip->getStart(), $archivedTripIdentifier->getId());
            $this->deleteTripEvent($tripId);

            $noteService->updateNotesOwner($tripId, $archivedTripIdentifier->getId());
            
            return $this->getCandidateTrip($archivedTripIdentifier->getId());
        }

        private function getTripEventId($tripId) : ?string {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id FROM trip_event WHERE trip_id = ?")
                ->withParameters($tripId)
                ->getSingleColumn("id");
        }
        
        private function deleteTripEvent($tripId) : bool {
            global $googleApiClient;
                
            return $googleApiClient->deleteCalendarEvent("trips", $this->getTripEventId($tripId));
        }
    }
?>