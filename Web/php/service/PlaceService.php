<?php
    require_once(dirname(__FILE__) . "/../model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/../processor/GetCoordsProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetChatResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetPlacesProcessor.php");

    class PlaceService {
        public function getRegularPlace($placeId) {
            $places = (new GetPlacesProcessor())
                ->process(array(
                    "placeId" => $placeId));
            return count($places) === 1 ? $places[0] : NULL;
        }

        public function getPlaceIdentifier($name, $country) {
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

        public function getOrCreatePlaceIdentifier($name, $country, $address) {            
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

        private function getSuggestedExcerpt($name, $country) {
            global $configuration;

            return (new GetChatResponseProcessor())
                ->process(array(
                    "query" => sprintf($configuration["chatRequests"]["suggestedExcerpt"], $name, $country)));
        }
    }
?>