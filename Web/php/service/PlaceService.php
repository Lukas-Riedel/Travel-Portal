<?php
    require_once(dirname(__FILE__) . "/../model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Place.php");
    require_once(dirname(__FILE__) . "/../processor/GetCoordsProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetChatResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetPlacesProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetCandidatePlacesProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/UpdateAlbumProcessor.php");

    class PlaceService {
        public function getRegularPlaces($tripId) : array {
            return (new GetPlacesProcessor())
                ->process(array(
                    "tripId" => $tripId));
        }

        public function getCandidatePlaces($tripId) : array {
            return (new GetCandidatePlacesProcessor())
                ->process(array(
                    "tripId" => $tripId));
        }

        public function getRegularPlace($placeId) : ?Place {
            $places = (new GetPlacesProcessor())
                ->process(array(
                    "placeId" => $placeId));
            return count($places) === 1 ? $places[0] : NULL;
        }

        public function getPlaceIdentifier($name, $country) : ?PlaceIdentifier {
            global $databaseProvider, $highlightService;

            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                ->withParameters($name, $country)
                ->getFirstRow();

            if ($placeIdentifierRow === NULL) {
                return NULL;
            }

            if ($placeIdentifierRow["excerpt"] === NULL) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET excerpt = ? WHERE id = ?")
                    ->withParameters($this->getSuggestedExcerpt($name, $country), $placeIdentifierRow["id"])
                    ->execute();

                $placeIdentifierRow = $databaseProvider
                    ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                    ->withParameters($name, $country)
                    ->getFirstRow();
            }

            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function getPlaceIdentifierById($placeId) : ?PlaceIdentifier {
            global $databaseProvider, $highlightService;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($placeId)
                ->getSingleRow();

            if ($placeIdentifierRow === NULL) {
                return NULL;
            }
            
            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function updatePlaceMainHighlight($placeId, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceExcerpt($placeId, $excerpt) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET excerpt = ? WHERE id = ?")
                ->withParameters($excerpt, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceName($placeId, $name) : bool {
            global $databaseProvider, $googleApiClient, $albumService, $schedulingProvider;

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET name = ?, excerpt = NULL WHERE id = ?")
                ->withParameters($name, $placeId)
                ->execute() === 1;

            $place = $this->getRegularPlace($placeId);
            if ($place !== NULL) {
                foreach ($place->getDates() as &$date) {                       
                    $album = $date->getAlbum();
                    if ($album !== NULL) {     
                        $externalAlbumId = $albumService->getExternalIdentifier($album->getId());
                        $wasUpdated &= $googleApiClient->updateAlbumName($externalAlbumId, str_replace($place->getName(), $name, $album->getName()));
                        $albumService->updateAlbum($album->getId());
                    }

                    $eventId = $this->getPlaceEventId($placeId, $date->getStart());
                    if ($eventId !== NULL) {  
                        $wasUpdated &= $googleApiClient->updateCalendarEventSummary("places", $eventId, $name);
                    }
                }
            }
            
            foreach ($this->getContainedTripIdentifiers($placeId) as &$tripId) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $tripId), NULL); 
            }   

            return $wasUpdated;
        }

        public function updatePlaceLocation($placeId, $latitude, $longitude) : bool {
            global $databaseProvider, $schedulingProvider;

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET latitude = ?, longitude = ? WHERE id = ?")
                ->withParameters($latitude, $longitude, $placeId)
                ->execute() === 1;
            
            foreach ($this->getContainedTripIdentifiers($placeId) as &$tripId) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $tripId), NULL); 
            }   

            return $wasUpdated;
        }

        public function getAllPlaceIdentifiers() : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier")
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getPlaceIdentifiersByCoordinates($latitude, $longitude) : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE latitude = ? AND longitude = ?")
                ->withParameters($latitude, $longitude)
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getPlaceIdentifiersByCountry($country) : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE country = ?")
                ->withParameters($country)
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getPlaceIdentifiersByCategoryId($categoryId) : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id IN (SELECT place_id FROM category WHERE category_id = ?)")
                ->withParameters($categoryId)
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getOrCreatePlaceIdentifier($name, $country, $address) : PlaceIdentifier {            
            global $databaseProvider, $configuration, $schedulingProvider;

            $placeIdentifier = $this->getPlaceIdentifier($name, $country);
            if ($placeIdentifier !== NULL) {
                return $placeIdentifier;
            }

            if ($country == $configuration["countryNames"]["UNKNOWN"]) {
                throw new InvalidArgumentException("Cannot create an identifier for an unknown country.");
            }
            
            $location = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $address));

            $databaseProvider
                ->statementBuilder("INSERT INTO place_identifier (name, country, timezone, latitude, longitude, excerpt) VALUES (?, ?, ?, ?, ?, ?)")
                ->withParameters($name, $country, $location->getTimezone(), $location->getLatitude(), $location->getLongitude(), $this->getSuggestedExcerpt($name, $country))
                ->execute();

            $placeIdentifier = $this->getPlaceIdentifier($name, $country);
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateCategories", array(
                    "placeId" => $placeIdentifier->getId()), NULL);

            return $placeIdentifier;
        }

        public function createPermanentPlace($name, $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Permanent, $name, $address);
        }

        public function createCandidatePlace($name, $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Candidate, $name, $address);
        }

        public function archivePlaces($tripId, $tripStart, $archivedTripId) : array {
            global $configuration, $databaseProvider;

            $places = $this->getRegularPlaces($tripId);
            
            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timeOffset = $this->getTimezoneOffset($date->getStart(), $configuration["homeLocation"]["timezone"], $place->getTimezone());
                    $databaseProvider
                        ->statementBuilder("INSERT INTO place_candidate_event (place_id, trip_id, start, end) VALUES (?, ?, ?, ?)")
                        ->withParameters($place->getId(), $archivedTripId, $date->getStart() - $timeOffset - $tripStart, $date->getEnd() - $timeOffset - $tripStart)
                        ->execute();
                    $this->deletePlaceEvent($place->getId(), $date->getStart());
                }
            }
            
            return $this->getCandidatePlaces($archivedTripId);
        }

        private function createSpecialPlace($specialPlaceType, $name, $address) : Place {            
            global $databaseProvider, $configurationService;

            $placeTable = $this->resolveSpecialPlaceTable($specialPlaceType);
            $country = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $address))->getCountry();

            $placeIdentifier = $this->getOrCreatePlaceIdentifier($name, $country, $address);

            // TODO: Remove the create-if-not-exists semantics.
            $databaseProvider
                ->statementBuilder("DELETE FROM " . $placeTable . " WHERE place_id = ?")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO " . $placeTable . " (place_id) VALUES (?)")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            $configurationService->updateConfigurationVisibility(array("public", "modifiable"), "COUNTRIES", $placeIdentifier->getCountry());
    
            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getExcerpt(), array(), array(), array());
        }

        private function getSuggestedExcerpt($name, $country) : ?string {
            global $configuration;

            return (new GetChatResponseProcessor())
                ->process(array(
                    "query" => sprintf($configuration["chatRequests"]["suggestedExcerpt"], $name, $country)));
        }

        private function resolveSpecialPlaceTable($specialPlaceType) {
            if ($specialPlaceType === SpecialPlaceType::Candidate) {
                return "place_candidate";
            }
            if ($specialPlaceType === SpecialPlaceType::Permanent) {
                return "place_permanent";
            }
            throw new InvalidArgumentException("Unknown special place type " . $specialPlaceType . ".");
        }

        private function getPlaceEventId($placeId, $start) : ?string {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id FROM place_event WHERE place_id = ? AND start = ?")
                ->withParameters($placeId, $start)
                ->getSingleColumn("id");
        }

        private function getContainedTripIdentifiers($placeId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT DISTINCT trip_id FROM place_summary WHERE place_id = ? AND trip_id IS NOT NULL")
                ->withParameters($placeId)
                ->getResultSetForColumn("trip_id");
        }
        
        private function deletePlaceEvent($placeId, $start) : bool {
            global $googleApiClient;
                
            return $googleApiClient->deleteCalendarEvent("places", $this->getPlaceEventId($placeId, $start));
        }

        private function getTimezoneOffset($timestamp, $fromTimezone, $toTimezone) {
            $timezone = new DateTimeZone($fromTimezone);
            $dateTimeHome = new DateTime(date('m/d/Y H:i:s', $timestamp), new DateTimeZone($toTimezone));
            return $timezone->getOffset($dateTimeHome) - (new DateTimeZone($toTimezone))->getOffset($dateTimeHome);
        }
    }

    enum SpecialPlaceType {
        case Candidate;
        case Permanent;
    }
?>