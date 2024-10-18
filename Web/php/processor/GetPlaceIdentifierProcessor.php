<?php 
    require_once(dirname(__FILE__) . "/../model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/HighlightIdentifier.php");
    require_once(dirname(__FILE__) . "/GetCoordsProcessor.php");
    require_once(dirname(__FILE__) . "/GetChatResponseProcessor.php");

    class GetPlaceIdentifierProcessor extends Processor {   
        public function process($input) {
            global $databaseProvider, $configuration, $schedulingProvider;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                ->withParameters($input["name"], $input["country"])
                ->getFirstRow();

            if ($placeIdentifierRow != NULL) {
                if ($placeIdentifierRow["excerpt"] == NULL) {
                    $databaseProvider
                        ->statementBuilder("UPDATE place_identifier SET excerpt = ? WHERE id = ?")
                        ->withParameters($this->getSuggestedExcerpt($input), $placeIdentifierRow["id"])
                        ->execute();

                    $placeIdentifierRow = $databaseProvider
                        ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                        ->withParameters($input["name"], $input["country"])
                        ->getFirstRow();
                }

                return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                    $placeIdentifierRow["timezone"], $this->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
            }

            if ($input["country"] == $configuration["countryNames"]["UNKNOWN"]) {
                throw new InvalidArgumentException("Unable to create identifier for unknown country.");
            }

            $address = $input["name"] . ", " . $input["country"];
            if (isset($input["address"])) {
                $address = $input["address"];
            }
            
            $location = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $address));

            $databaseProvider
                ->statementBuilder("INSERT INTO place_identifier (name, country, timezone, latitude, longitude, excerpt) VALUES (?, ?, ?, ?, ?, ?)")
                ->withParameters($input["name"], $input["country"], $location->getTimezone(), $location->getLatitude(), $location->getLongitude(), $this->getSuggestedExcerpt($input))
                ->execute();

            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                ->withParameters($input["name"], $input["country"])
                ->getFirstRow();
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateCategories", array(
                    "placeId" => $placeIdentifierRow["id"]), NULL);

            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $this->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function getRequiredArguments() {
            return array("name", "country");
        }

        public function requiresAdminRole() {
            return FALSE;
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();
            
           return $highlightRow == NULL ? NULL : new HighlightIdentifier($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $highlightRow["focal_length"], $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
        }

        private function getSuggestedExcerpt($input) {
            global $configuration;

            return (new GetChatResponseProcessor())
                ->process(array(
                    "query" => sprintf($configuration["chatRequests"]["suggestedExcerpt"], $input["name"], $input["country"])));
        }
    }
?>