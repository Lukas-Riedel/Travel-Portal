<?php
    namespace Core\Service\Geocoding;

    use Core\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Client\Google\GoogleClient;

    class GeocodingService {

        private const EARTH_RADIUS_KM = 6378;
        
        private const CACHED_ADDRESS_PATTERN = "{.+, (.+) \((.+) (.+)\) \[(.+)\]}";
        private const CACHED_ADDRESS_FORMAT = "%s, %s (%s %s) [%s]";
        
        private const ADDRESS_CACHE_KEY_FORMAT = "GeocodingService:Address:%s";
        private const ADDRESS_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;
        
        private const LOCATION_CACHE_KEY_FORMAT = "GeocodingService:Location:%s-%s";
        private const LOCATION_CACHE_TTL = CommonConstants::ONE_MONTH_SECONDS;

        private readonly CacheClient $cacheClient;

        private readonly GoogleClient $googleClient;

        public function __construct(CacheClient $cacheClient, GoogleClient $googleClient) {
            $this->cacheClient = $cacheClient;
            $this->googleClient = $googleClient;
        }

        public function getAddress(float $latitude, float $longitude, bool $fetchIfNotPresent = true) : ?Address {
            $address = $this->tryGetCachedAddress($latitude, $longitude);
            if ($address !== null || !$fetchIfNotPresent) {
                return $address;
            }

            return $this->createAddress($latitude, $longitude);
        }

        public function getLocation(string $address, bool $fetchIfNotPresent = true) : ?Location {
            $location = $this->tryParseLocation($address);
            if ($location !== null) {
                return $location;
            }

            $location = $this->tryGetCachedLocation($address);
            if ($location !== null || !$fetchIfNotPresent) {
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
            $resolvedLocation = $this->googleClient->getLocation($address);
            if ($resolvedLocation !== null) {
                $country = $this->extractCountryName($resolvedLocation);
                $latitude = $resolvedLocation["geometry"]["location"]["lat"];
                $longitude = $resolvedLocation["geometry"]["location"]["lng"];
            }

            // Timezone request.
            if ($latitude !== null && $longitude !== null) {
                $timezone = $this->googleClient->getTimezone($latitude, $longitude);
            }

            $convertedLocation = new Location($country, $latitude, $longitude, $timezone);
            $this->cacheClient->set($this->getAddressCacheKey($address), $convertedLocation, self::ADDRESS_CACHE_TTL);

            return $convertedLocation;
        }

        private function extractCountryName(mixed $resolvedLocation) : ?string {
            foreach ($resolvedLocation["address_components"] as &$addressComponent) {
                if (in_array("country", $addressComponent["types"])) {
                    return mb_strtoupper(mb_substr($addressComponent["long_name"], 0, 1)) . mb_substr($addressComponent["long_name"], 1);
                }
            }

            return null;
        }

        private function createAddress(float $latitude, float $longitude) : Address {
            $address = $this->googleClient->getAddress($latitude, $longitude);

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