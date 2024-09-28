<?php
    require_once(dirname(__FILE__) . "/../model/Place.php");
    require_once(dirname(__FILE__) . "/GetPlaceIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetCoordsProcessor.php");

    class AddSpecialPlaceProcessor extends Processor {  
        public function process($input) {
            global $databaseProvider;

            $table = $this->resolveTable($input["type"]);

            $country = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $input["address"]))->getCountry();

            $placeIdentifier = (new GetPlaceIdentifierProcessor())
                ->process(array(
                    "name" => $input["name"],
                    "country" => $country,
                    "address" => $input["address"]));

            $databaseProvider
                ->statementBuilder("DELETE FROM " . $table . " WHERE place_id = ?")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO " . $table . " (place_id) VALUES (?)")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            $databaseProvider
                ->statementBuilder("UPDATE configuration SET levels = 'public,modifiable' WHERE type = 'COUNTRIES' AND `key` = ?")
                ->withParameters($placeIdentifier->getCountry())
                ->execute();
    
            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), array(), array(), array());
        }

        public function getRequiredArguments() {
            return array("type", "name", "address");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function resolveTable($type) {
            if ($type == "candidate") {
                return "place_candidate";
            }
            if ($type == "permanent") {
                return "place_permanent";
            }
            throw new InvalidArgumentException("Unknown place type " . $type . ". Permitted values: candidate, permanent");
        }
    }
?>