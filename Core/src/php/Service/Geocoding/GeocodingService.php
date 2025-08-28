<?php
    namespace Core\Service\Geocoding;

use Core\Service\Configuration\ConfigurationService;

    class GeocodingService {

        private const EARTH_RADIUS_KM = 6378;
        
        private const CACHED_ADDRESS_PATTERN = "{.+, (.+) \((.+), (.+)\)}";
        private const CACHED_ADDRESS_FORMAT = "%s, %s (%s, %s)";

        private const GET_LOCATION_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/geocode/json?key=%s&language=en&address=%s";
        private const GET_TIMEZONE_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/timezone/json?key=%s&location=%s,%s&timestamp=0";

        private readonly GeocodingMapper $geocodingMapper;
        
        private readonly ConfigurationService $configurationService;

        private readonly \HttpClient $httpClient;

        public function __construct(\DatabaseProvider $databaseProvider, ConfigurationService $configurationService, \HttpClient $httpClient) {
            $this->geocodingMapper = new GeocodingMapper($databaseProvider);
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
        }

        public function getAddress(string $placeName, Location $location) : string {
            return sprintf(self::CACHED_ADDRESS_FORMAT, $placeName, $location->getCountry(), $location->getLatitude(), $location->getLongitude());
        }

        public function getLocation(string $address) : Location {
            $location = $this->doGetLocation($address);
            if ($location !== null) {
                return $location;
            }

            $this->createLocation($address);
            return $this->doGetLocation($address);
        }

        private function doGetLocation(string $address) : ?Location {
            $location = $this->geocodingMapper->selectLocation($address);
            if ($location === null) {
                return null;
            }

            $country = null;
            $countryNames = $this->configurationService->getConfigurationEntry("countryNames");
            if ($location->getCountry() === null) {
                // TODO: Remove the UNKNOWN country, use null instead.
                $country = array_values(array_filter($countryNames, fn($c) => $c["country"] == "UNKNOWN"))[0]["name"];
            }
            else if (in_array($location->getCountry(), array_map(fn($c) => $c["country"], $countryNames))) {
                $country = array_values(array_filter($countryNames, fn($c) => $c["country"] == $location->getCountry()))[0]["name"];
            }
            else {
                throw new \RuntimeException("Unknown country '" . $location->getCountry() . "' encountered.");
            }

            return new Location($country, $location->getLatitude(), $location->getLongitude(), $location->getTimezone());
        }

        private function createLocation(string $address) : void {        
            $country = null;
            $latitude = null;
            $longitude = null;
            $timezone = null;

            // Quick path - read all necessary data (except timezone) from the address string.
            preg_match(self::CACHED_ADDRESS_PATTERN, $address, $tokens);
            if (count($tokens) === 4) {
                $country = array_values(array_filter($this->configurationService->getConfigurationEntry("countryNames"), fn($country) => $country["name"] == $tokens[1]))[0]["country"];
                $latitude = $tokens[2];
                $longitude = $tokens[3];
            }

            // Geocoding request.
            if ($country === null || $latitude === null || $longitude === null) {
                $apiResponse = $this->httpClient->executeRequest(\HttpMethod::GET, sprintf(self::GET_LOCATION_ENDPOINT_FORMAT, GOOGLE_MAPS_API_KEY, urlencode($address)));
    
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
            if ($latitude !== null && $longitude !== null && $timezone === null) {    
                $apiResponse = $this->httpClient->executeRequest(\HttpMethod::GET, sprintf(self::GET_TIMEZONE_ENDPOINT_FORMAT, GOOGLE_MAPS_API_KEY, $latitude, $longitude));
                
                if (array_key_exists("timeZoneId", $apiResponse)) {
                    $timezone = $apiResponse["timeZoneId"];
                }
            }

            $this->geocodingMapper->insertLocation(new Location($country, $latitude, $longitude, $timezone), $address);
        }

        public function getDistance(float $aLatitude, float $aLongitude, float $bLatitude, float $bLongitude) : float {
            $alpha = ($bLatitude - $aLatitude) / 2;
            $beta = ($bLongitude - $aLongitude) / 2;
            $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($aLatitude))
                * cos(deg2rad($bLatitude)) * sin(deg2rad($beta)) * sin(deg2rad($beta));
            $c = asin(min(1, sqrt($a)));
            return 2 * self::EARTH_RADIUS_KM * $c;
        }
    }
?>