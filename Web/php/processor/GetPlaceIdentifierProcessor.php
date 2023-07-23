<?php 
    require_once(dirname(__FILE__) . "/../model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/GetCoordsProcessor.php");

    class GetPlaceIdentifierProcessor extends Processor {   
        public function process($input) {
            global $databaseProvider, $configuration, $schedulingProvider;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                ->withParameters($input["name"], $input["country"])
                ->getFirstRow();

            if ($placeIdentifierRow != NULL) {
                return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"], $placeIdentifierRow["timezone"]);
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
                ->statementBuilder("INSERT INTO place_identifier (name, country, timezone, latitude, longitude) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($input["name"], $input["country"], $location->getTimezone(), $location->getLatitude(), $location->getLongitude())
                ->execute();

            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                ->withParameters($input["name"], $input["country"])
                ->getFirstRow();
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateCategories", array(
                    "placeId" => $placeIdentifierRow["id"]), NULL);

            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"], $placeIdentifierRow["timezone"]);
        }

        public function getRequiredArguments() {
            return array("name", "country");
        }

        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>