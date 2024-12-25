<?php
    require_once(dirname(__FILE__) . "/../model/Location.php");

    class GetCoordsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration, $httpClient;

            $locationRow = $databaseProvider
                ->statementBuilder("SELECT address, country, timezone, latitude, longitude FROM cache_location WHERE address = ?")
                ->withParameters($input["address"])
                ->getFirstRow();

            if ($locationRow != NULL) {
                $databaseProvider
                    ->statementBuilder("UPDATE cache_location SET last_access = UNIX_TIMESTAMP() WHERE address = ?")
                    ->withParameters($locationRow["address"])
                    ->execute();

                if (!array_key_exists($locationRow["country"], $configuration["countryNames"])) {
                    throw new RuntimeException("Unknown country " . $locationRow["country"] . " encountered.");
                }

                return new Location($configuration["countryNames"][$locationRow["country"]], $locationRow["latitude"], $locationRow["longitude"], $locationRow["timezone"]);
            }
        
            $country = "UNKNOWN";
            $latitude = "UNKNOWN";
            $longitude = "UNKNOWN";
            $timezone = "UNKNOWN";

            // Quick path - read all necessary data (except timezone) from the address string.
            preg_match("{.+, (.+) \((.+), (.+)\)}", $input["address"], $tokens);
            if (count($tokens) == 4) {
                $countryCandidate = $databaseProvider
                    ->statementBuilder("SELECT `key` FROM configuration WHERE type = 'COUNTRY_NAMES' AND value = ?")
                    ->withParameters($tokens[1])
                    ->getSingleColumn("key");

                if ($countryCandidate != NULL) {
                    $country = $countryCandidate;
                }

                $latitude = $tokens[2];
                $longitude = $tokens[3];
            }

            // Geocoding request.
            if ($country == "UNKNOWN" || $latitude == "UNKNOWN" || $longitude == "UNKNOWN") {
                $apiResponse = $httpClient->executeRequest("GET", "https://maps.googleapis.com/maps/api/geocode/json?key=" . $configuration["googleMapsApiKeys"]["ipAddress"] . "&language=en&address=" . rawurlencode($input["address"]));
    
                if ($apiResponse["status"] == "OK") {
                    if (count($apiResponse["results"]) > 0) {
                        $resolvedLocation = $apiResponse["results"][0];
    
                        $latitude = $resolvedLocation["geometry"]["location"]["lat"];
                        $longitude = $resolvedLocation["geometry"]["location"]["lng"];
                        
                        foreach ($resolvedLocation["address_components"] as &$addressComponent) {
                            if (in_array("country", $addressComponent["types"])) {
                                $country = $addressComponent["long_name"];
                                break;
                            }
                        }
                    }
                }
            }

            // Timezone request.
            if ($latitude != "UNKNOWN" && $longitude != "UNKNOWN" && $timezone == "UNKNOWN") {    
                $apiResponse = $httpClient->executeRequest("GET", "https://maps.googleapis.com/maps/api/timezone/json?key=" . $configuration["googleMapsApiKeys"]["ipAddress"] . "&location=" . $latitude . "," . $longitude . "&timestamp=0");
                
                if (array_key_exists("timeZoneId", $apiResponse)) {
                    $timezone = $apiResponse["timeZoneId"];
                }
            }
    
            $databaseProvider
                ->statementBuilder("INSERT INTO cache_location (address, country, timezone, latitude, longitude, last_access) VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())")
                ->withParameters($input["address"], $country, $timezone, $latitude, $longitude)
                ->execute();

            return $this->process($input);
        }

        public function getRequiredArguments() {
            return array("address");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function endsWith($string, $suffix) {
            return substr($string, (-1) * strlen($suffix)) === $suffix;
        }
    }
?>