<?php
    require_once(dirname(__FILE__) . "/../model/AirportIdentifier.php");
    require_once(dirname(__FILE__) . "/../processor/GetCoordsProcessor.php");

    class FlightService {

        public function getAirportIdentifier($code) : ?AirportIdentifier {
            global $databaseProvider;
            
            $airportIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM airport_identifier WHERE code = ?")
                ->withParameters($code)
                ->getFirstRow();

            if ($airportIdentifierRow === NULL) {
                return NULL;
            }
            
            return new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"], $airportIdentifierRow["country"], 
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }
        
        public function getOrCreateAirportIdentifier($code) : AirportIdentifier {
            global $databaseProvider;

            $airportIdentifier = $this->getAirportIdentifier($code);
            if ($airportIdentifier !== NULL) {
                return $airportIdentifier;
            }
            
            $location = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $code . " Airport"));

            $databaseProvider
                ->statementBuilder("INSERT INTO airport_identifier (code, latitude, longitude, country, timezone) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($code, $location->getLatitude(), $location->getLongitude(), $location->getCountry(), $location->getTimezone())
                ->execute();

            return $this->getAirportIdentifier($code);
        }
    }
?>