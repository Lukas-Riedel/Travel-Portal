<?php  
    require_once(dirname(__FILE__) . "/../model/AirportIdentifier.php");
    require_once(dirname(__FILE__) . "/GetCoordsProcessor.php");

    class GetAirportIdentifierProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $airportIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM airport_identifier WHERE code = ?")
                ->withParameters($input["code"])
                ->getFirstRow();

            if ($airportIdentifierRow != NULL) {
                return new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"], $airportIdentifierRow["country"], 
                    $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
            }
            
            $location = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $input["code"] . " Airport"));

            $databaseProvider
                ->statementBuilder("INSERT INTO airport_identifier (code, latitude, longitude, country, timezone) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($input["code"], $location->getLatitude(), $location->getLongitude(), $location->getCountry(), $location->getTimezone())
                ->execute();

            $airportIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM airport_identifier WHERE code = ?")
                ->withParameters($input["code"])
                ->getFirstRow();

            return new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"], $airportIdentifierRow["country"], 
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }

        public function getRequiredArguments() {
            return array("code");
        }

        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>