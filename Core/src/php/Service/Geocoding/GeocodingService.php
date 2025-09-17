<?php
    namespace Core\Service\Geocoding;

    use Core\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;

    class GeocodingService {

        private const EARTH_RADIUS_KM = 6378;
        
        private const CACHED_ADDRESS_PATTERN = "{.+, (.+) \((.+) (.+)\) \[(.+)\]}";
        private const CACHED_ADDRESS_FORMAT = "%s, %s (%s %s) [%s]";
        
        private const ADDRESS_CACHE_KEY_FORMAT = "GeocodingService:Address:%s";
        private const ADDRESS_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;
        
        private const LOCATION_CACHE_KEY_FORMAT = "GeocodingService:Location:%s-%s";
        private const LOCATION_CACHE_TTL = CommonConstants::ONE_MONTH_SECONDS;

        // TODO: Move to GoogleClient.
        private const GET_LOCATION_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/geocode/json?key=%s&language=en&address=%s";
        private const GET_ADDRESS_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/geocode/json?key=%s&language=cs&latlng=%s,%s";
        private const GET_TIMEZONE_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/timezone/json?key=%s&location=%s,%s&timestamp=0";
        
        private readonly ConfigurationService $configurationService;

        private readonly CacheClient $cacheClient;

        private readonly HttpClient $httpClient;

        public function __construct(ConfigurationService $configurationService, CacheClient $cacheClient, HttpClient $httpClient) {
            $this->configurationService = $configurationService;
            $this->cacheClient = $cacheClient;
            $this->httpClient = $httpClient;
        }

        public function getAddress(float $latitude, float $longitude,  bool $apiFetchEnabled = true) : ?Address {
            $address = $this->tryGetCachedAddress($latitude, $longitude);
            if ($address !== null || !$apiFetchEnabled) {
                return $address;
            }

            return $this->createAddress($latitude, $longitude);
        }

        public function getLocation(string $address, bool $apiFetchEnabled = true) : ?Location {
            $location = $this->tryParseLocation($address);
            if ($location !== null) {
                return $location;
            }

            $location = $this->tryGetCachedLocation($address);
            if ($location !== null || !$apiFetchEnabled) {
                return $location;
            }

            return $this->createLocation($address);
        }

        public function getFormattedAddress(string $placeName, Location $location) : string {
            return sprintf(self::CACHED_ADDRESS_FORMAT, $placeName, $location->getCountry(), $location->getLatitude(), $location->getLongitude(), $location->getTimezone());
        }

        public function getDistance(float $aLatitude, float $aLongitude, float $bLatitude, float $bLongitude) : float {
            $alpha = ($bLatitude - $aLatitude) / 2;
            $beta = ($bLongitude - $aLongitude) / 2;
            $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($aLatitude))
                * cos(deg2rad($bLatitude)) * sin(deg2rad($beta)) * sin(deg2rad($beta));
            $c = asin(min(1, sqrt($a)));
            return 2 * self::EARTH_RADIUS_KM * $c;
        }

        private function tryGetCachedLocation(string $address) : ?Location {
            $location = $this->cacheClient->get($this->getAddressCacheKey($address), self::ADDRESS_CACHE_TTL);
            if ($location === null) {
                return null;
            }

            return new Location($location["country"], $location["latitude"], $location["longitude"], $location["timezone"]);
        }

        private function tryGetCachedAddress(float $latitude, float $longitude) : ?Address {
            $address = $this->cacheClient->get($this->getLocationCacheKey($latitude, $longitude), self::LOCATION_CACHE_TTL);
            if ($address === null) {
                return null;
            }

            return new Address($address["address"]);
        }

        private function tryParseLocation(string $address) : ?Location {
            preg_match(self::CACHED_ADDRESS_PATTERN, $address, $tokens);
            if (count($tokens) !== 5) {
                return null;
            }
            
            return new Location($tokens[1], $tokens[2], $tokens[3], $tokens[4]);
        }

        private function createLocation(string $address) : Location {
            $country = null;
            $latitude = null;
            $longitude = null;
            $timezone = null;

            // Geocoding request.
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_LOCATION_ENDPOINT_FORMAT, GOOGLE_MAPS_API_KEY, urlencode($address)));

            if ($apiResponse["status"] === "OK") {
                if (count($apiResponse["results"]) > 0) {
                    $resolvedLocation = $apiResponse["results"][0];

                    $latitude = $resolvedLocation["geometry"]["location"]["lat"];
                    $longitude = $resolvedLocation["geometry"]["location"]["lng"];
                    
                    foreach ($resolvedLocation["address_components"] as &$addressComponent) {
                        if (in_array("country", $addressComponent["types"])) {
                            $countryNames = $this->configurationService->getConfigurationEntry("countryNames");
                            if ($addressComponent["long_name"] === null) {
                                // TODO: Remove the UNKNOWN country, use null instead.
                                $country = array_values(array_filter($countryNames, fn($c) => $c["country"] == "UNKNOWN"))[0]["name"];
                            }
                            else if (in_array($addressComponent["long_name"], array_map(fn($c) => $c["country"], $countryNames))) {
                                $country = array_values(array_filter($countryNames, fn($c) => $c["country"] == $addressComponent["long_name"]))[0]["name"];
                            }
                            else {
                                throw new \RuntimeException("Unknown country '" . $addressComponent["long_name"] . "' encountered.");
                            }
                            break;
                        }
                    }
                }
            }

            // Timezone request.
            if ($latitude !== null && $longitude !== null && $timezone === null) {    
                $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_TIMEZONE_ENDPOINT_FORMAT, GOOGLE_MAPS_API_KEY, $latitude, $longitude));
                
                if (array_key_exists("timeZoneId", $apiResponse)) {
                    $timezone = $apiResponse["timeZoneId"];
                }
            }

            $convertedLocation = new Location($country, $latitude, $longitude, $timezone);
            $this->cacheClient->set($this->getAddressCacheKey($address), $convertedLocation, self::ADDRESS_CACHE_TTL);

            return $convertedLocation;
        }

        private function createAddress(float $latitude, float $longitude) : Address {
            $address = null;

            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ADDRESS_ENDPOINT_FORMAT, GOOGLE_MAPS_API_KEY, $latitude, $longitude));

            if ($apiResponse["status"] === "OK") {
                if (count($apiResponse["results"]) > 0) {
                    if (isset($apiResponse["results"][0]["formatted_address"])) {
                        $address = $apiResponse["results"][0]["formatted_address"];
                    }
                }
            }

            $convertedAddress = new Address($address);
            $this->cacheClient->set($this->getLocationCacheKey($latitude, $longitude), $convertedAddress, self::LOCATION_CACHE_TTL);

            return $convertedAddress;
        }

        private function getAddressCacheKey(string $address) : string {
            return sprintf(self::ADDRESS_CACHE_KEY_FORMAT, $address);
        }

        private function getLocationCacheKey(float $latitude, float $longitude) : string {
            return sprintf(self::LOCATION_CACHE_KEY_FORMAT, round($latitude, 3), round($longitude, 3));
        }
    }
?>