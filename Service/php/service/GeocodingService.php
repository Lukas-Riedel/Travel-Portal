<?php
    require_once(dirname(__FILE__) . "/GeocodingMapper.php");
    require_once(dirname(__FILE__) . "/../model/Location.php");
    
    class GeocodingService {

        private const EARTH_RADIUS_KM = 6378;
        
        private const UNKNOWN_COUNTRY_KEY = "UNKNOWN";
        private const CACHED_ADDRESS_PATTERN = "{.+, (.+) \((.+), (.+)\)}";
        private const CACHED_ADDRESS_FORMAT = "%s, %s (%s, %s)";

        private const GET_LOCATION_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/geocode/json?key=%s&language=en&address=%s";
        private const GET_TIMEZONE_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/timezone/json?key=%s&location=%s,%s&timestamp=0";

        private readonly GeocodingMapper $geocodingMapper;
        
        private readonly ConfigurationService $configurationService;

        private readonly HttpClient $httpClient;

        public function __construct(DatabaseProvider $databaseProvider, ConfigurationService $configurationService, HttpClient $httpClient) {
            $this->geocodingMapper = new GeocodingMapper($databaseProvider);
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
        }

        public function getAddress(string $placeName, Location $location) : string {
            return sprintf(self::CACHED_ADDRESS_FORMAT, $placeName, $location->getCountry(), $location->getLatitude(), $location->getLongitude());
        }

        public function getLocation(string $address) : Location {
            $location = $this->doGetLocation($address);
            if ($location !== NULL) {
                return $location;
            }

            $this->createLocation($address);
            return $this->doGetLocation($address);
        }

        private function doGetLocation(string $address) : ?Location {
            $location = $this->geocodingMapper->selectLocation($address);
            if ($location === NULL) {
                return NULL;
            }

            $country = NULL;
            if ($location->getCountry() === NULL) {
                // TODO: Remove the UNKNOWN country, use null instead.
                $country = $this->configurationService->getConfigurationForTypeAndKey("countryNames", self::UNKNOWN_COUNTRY_KEY);
            }
            else if ($this->configurationService->existsForTypeAndKey("countryNames", $location->getCountry())) {
                $country = $this->configurationService->getConfigurationForTypeAndKey("countryNames", $location->getCountry());
            }
            else {
                throw new RuntimeException("Unknown country '" . $location->getCountry() . "' encountered.");
            }

            return new Location($country, $location->getLatitude(), $location->getLongitude(), $location->getTimezone());
        }

        private function createLocation(string $address) : void {        
            $country = NULL;
            $latitude = NULL;
            $longitude = NULL;
            $timezone = NULL;

            // Quick path - read all necessary data (except timezone) from the address string.
            preg_match(self::CACHED_ADDRESS_PATTERN, $address, $tokens);
            if (count($tokens) === 4) {
                $country = $this->configurationService->getConfigurationKeyForTypeAndValue("countryNames", $tokens[1]);
                $latitude = $tokens[2];
                $longitude = $tokens[3];
            }

            // Geocoding request.
            if ($country === NULL || $latitude === NULL || $longitude === NULL) {
                $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_LOCATION_ENDPOINT_FORMAT,
                    $this->configurationService->getConfigurationForTypeAndKey("googleMapsApiKeys", "ipAddress"), rawurlencode($address)));
    
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
                $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_TIMEZONE_ENDPOINT_FORMAT, 
                    $this->configurationService->getConfigurationForTypeAndKey("googleMapsApiKeys", "ipAddress"), $latitude, $longitude));
                
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