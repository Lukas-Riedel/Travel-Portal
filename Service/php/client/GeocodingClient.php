<?php
    require_once(dirname(__FILE__) . "/../model/Location.php");
    
    class GeocodingClient {
        public function getLocation($address) {
            global $databaseProvider, $configuration, $httpClient;

            $locationRow = $databaseProvider
                ->statementBuilder("SELECT address, country, timezone, latitude, longitude FROM cache_location WHERE address = ? ORDER BY last_access DESC")
                ->withParameters($address)
                ->getFirstRow();

            if ($locationRow !== NULL) {
                $databaseProvider
                    ->statementBuilder("UPDATE cache_location SET last_access = UNIX_TIMESTAMP() WHERE address = ?")
                    ->withParameters($locationRow["address"])
                    ->execute();

                $country = NULL;
                if ($locationRow["country"] === NULL) {
                    $country = $configuration["countryNames"]["UNKNOWN"];
                }
                else if (array_key_exists($locationRow["country"], $configuration["countryNames"])) {
                    $country = $configuration["countryNames"][$locationRow["country"]];
                }
                else {
                    throw new RuntimeException("Unknown country " . $locationRow["country"] . " encountered.");
                }

                return new Location($country, $locationRow["latitude"], $locationRow["longitude"], $locationRow["timezone"]);
            }
        
            $country = NULL;
            $latitude = NULL;
            $longitude = NULL;
            $timezone = NULL;

            // Quick path - read all necessary data (except timezone) from the address string.
            preg_match("{.+, (.+) \((.+), (.+)\)}", $address, $tokens);
            if (count($tokens) == 4) {
                $countryCandidate = $databaseProvider
                    ->statementBuilder("SELECT `key` FROM configuration WHERE type = 'COUNTRY_NAMES' AND value = ?")
                    ->withParameters($tokens[1])
                    ->getSingleColumn("key");

                if ($countryCandidate !== NULL) {
                    $country = $countryCandidate;
                }

                $latitude = $tokens[2];
                $longitude = $tokens[3];
            }

            // Geocoding request.
            if ($country === NULL || $latitude === NULL || $longitude === NULL) {
                $apiResponse = $httpClient->executeRequest("GET", "https://maps.googleapis.com/maps/api/geocode/json?key=" . $configuration["googleMapsApiKeys"]["ipAddress"] . "&language=en&address=" . rawurlencode($address));
    
                if ($apiResponse["status"] === "OK") {
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
            if ($latitude !== NULL && $longitude !== NULL && $timezone === NULL) {    
                $apiResponse = $httpClient->executeRequest("GET", "https://maps.googleapis.com/maps/api/timezone/json?key=" . $configuration["googleMapsApiKeys"]["ipAddress"] . "&location=" . $latitude . "," . $longitude . "&timestamp=0");
                
                if (array_key_exists("timeZoneId", $apiResponse)) {
                    $timezone = $apiResponse["timeZoneId"];
                }
            }
    
            $databaseProvider
                ->statementBuilder("INSERT INTO cache_location (address, country, timezone, latitude, longitude, last_access) VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())")
                ->withParameters($address, $country, $timezone, $latitude, $longitude)
                ->execute();

            return $this->getLocation($address);
        }

        public function getDistance($lat1, $lon1, $lat2, $lon2) : float {            
            $deltaLatitude = $lat2 - $lat1;
            $deltaLongitude = $lon2 - $lon1;

            $alpha = $deltaLatitude / 2;
            $beta = $deltaLongitude / 2;

            $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($beta)) * sin(deg2rad($beta));
            $c = asin(min(1, sqrt($a)));

            return 2 * 6378 * $c;
        }
    }
?>