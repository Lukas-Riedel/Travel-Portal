<?php
    namespace Core\Service\Geocoding;

    use Common\Client\Cache\CacheClient;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class GeocodingService {

        private const EARTH_RADIUS_KM = 6378;
        
        private const CACHED_ADDRESS_PATTERN = "{.+, (.+) \((.+) (.+) (.+)\) \[(.+)\]}";
        private const CACHED_ADDRESS_FORMAT = "%s, %s (%s %s %s) [%s]";
        
        private const ADDRESS_CACHE_KEY_FORMAT = "GeocodingService:Address:%s";
        private const ADDRESS_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;

        private const ELEVATION_CACHE_KEY_FORMAT = "GeocodingService:Elevation:%s-%s";
        private const ELEVATION_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;
        
        private const LOCATION_CACHE_KEY_FORMAT = "GeocodingService:Location:%s-%s";
        private const LOCATION_CACHE_TTL = CommonConstants::ONE_MONTH_SECONDS;

        private const AIRPORT_RADIUS_KM = 3.0;
        private const STAY_RADIUS_KM = 0.5;
        private const HOME_RADIUS_KM = 0.5;

        private readonly ConfigurationService $configurationService;
        private readonly CacheClient $distributedCacheClient;
        private readonly GoogleClient $googleClient;
        private readonly GenerativeContentClient $generativeContentClient;

        private ?TripService $tripService = null;

        public function __construct(ConfigurationService $configurationService, CacheClient $distributedCacheClient, GoogleClient $googleClient, GenerativeContentClient $generativeContentClient) {
            $this->configurationService = $configurationService;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->googleClient = $googleClient;
            $this->generativeContentClient = $generativeContentClient;
        }

        public function setTripService(TripService $tripService) : void {
            $this->tripService = $tripService;
        }

        public function getAddress(float $latitude, float $longitude, bool $fetchIfNotPresent = true) : ?Address {
            $address = $this->doGetAddress($latitude, $longitude, $fetchIfNotPresent);

            $homeLocation = $this->configurationService->getConfigurationEntry("homeLocation");
            if ($this->getDistance($latitude, $longitude, $homeLocation["latitude"], $homeLocation["longitude"]) < self::HOME_RADIUS_KM) {
                return new Address(AddressType::Home, $address->getName(), $address->getAddress());
            }

            if ($this->tripService !== null) {
                foreach ($this->tripService->getRegularTrips(null, null, null, array(TripIncludedEntity::Flights->value, TripIncludedEntity::Stays->value), TripSortingStrategy::OldestDescending) as &$trip) {
                    if (!$trip->isPastOrCurrent()) {
                        continue;
                    }

                    foreach ($trip->getFlights() as &$flight) {
                        foreach (array($flight->getFrom(), $flight->getTo()) as &$airport) {
                            if ($airport->getLatitude() !== null && $airport->getLongitude() !== null
                                && $this->getDistance($latitude, $longitude, $airport->getLatitude(), $airport->getLongitude()) < self::AIRPORT_RADIUS_KM) {
                                return new Address(AddressType::Airport, $airport->getLongName() ?? $address->getAddress(), $airport->getLongName() ?? $address->getAddress());
                            }
                        }
                    }

                    if (!$trip->isCurrent()) {
                        continue;
                    }

                    foreach ($trip->getStays() as &$stay) {
                        if ($stay->getAddress() === null) {
                            continue;
                        }
                        
                        $resolvedLocation = $this->getLocation($stay->getAddress());
                        if ($this->getDistance($latitude, $longitude, $resolvedLocation->getLatitude(), $resolvedLocation->getLongitude()) < self::STAY_RADIUS_KM) {
                            return new Address(AddressType::Stay, $stay->getName(), $address->getAddress());
                        }
                    }
                }
            }

            return $address;
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

        public function getFormattedAddress(string $placeName, Location $location) : ?string {
            // The timezone shall stay here (and shouldn't be obtained from the calendar event)
            // because Google Calendar performs "timezone approximation" when creating an event.
            // For example, it translates Asia/Muscat into Asia/Dubai.
            return $location->getCountry() === null ? null
                : sprintf(self::CACHED_ADDRESS_FORMAT, $placeName, $location->getCountry(),
                    $location->getLatitude(), $location->getLongitude(), $location->getElevation(), $location->getTimezone());
        }

        public function getDistance(float $aLatitude, float $aLongitude, float $bLatitude, float $bLongitude) : float {
            $alpha = ($bLatitude - $aLatitude) / 2;
            $beta = ($bLongitude - $aLongitude) / 2;
            $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($aLatitude))
                * cos(deg2rad($bLatitude)) * sin(deg2rad($beta)) * sin(deg2rad($beta));
            $c = asin(min(1, sqrt($a)));
            return 2 * self::EARTH_RADIUS_KM * $c;
        }

        public function getElevation(float $latitude, float $longitude) : int {
            $cacheKey = $this->getElevationCacheKey($latitude, $longitude);
            $elevation = $this->distributedCacheClient->get($cacheKey, self::ELEVATION_CACHE_TTL);
            if ($elevation !== null) {
                return $elevation;
            }

            $elevation = $this->googleClient->getElevation($latitude, $longitude);
            if ($elevation === null) {
                return 0;
            }

            $elevation = round($elevation);
            $this->distributedCacheClient->set($cacheKey, $elevation, self::ELEVATION_CACHE_TTL);
            return $elevation;
        }

        private function tryGetCachedLocation(string $address) : ?Location {
            $location = $this->distributedCacheClient->get($this->getAddressCacheKey($address), self::ADDRESS_CACHE_TTL);
            if ($location === null) {
                return null;
            }

            return new Location($location["country"], $location["latitude"], $location["longitude"], $location["elevation"], $location["timezone"]);
        }

        private function doGetAddress(float $latitude, float $longitude, bool $fetchIfNotPresent = true) : ?Address {
            $address = $this->tryGetCachedAddress($latitude, $longitude);
            if ($address !== null || !$fetchIfNotPresent) {
                return $address;
            }

            return $this->createAddress($latitude, $longitude);
        }

        private function tryGetCachedAddress(float $latitude, float $longitude) : ?Address {
            $address = $this->distributedCacheClient->get($this->getLocationCacheKey($latitude, $longitude), self::LOCATION_CACHE_TTL);
            if ($address === null) {
                return null;
            }

            return new Address(AddressType::from($address["type"]), $address["name"], $address["address"]);
        }

        private function tryParseLocation(string $address) : ?Location {
            preg_match(self::CACHED_ADDRESS_PATTERN, $address, $tokens);
            if (count($tokens) !== 6) {
                return null;
            }
            
            return new Location($tokens[1], $tokens[2], $tokens[3], $tokens[4], $tokens[5]);
        }

        private function createLocation(string $address) : Location {
            $country = null;
            $latitude = null;
            $longitude = null;
            $elevation = null;
            $timezone = null;

            // Geocoding request.
            $resolvedLocation = $this->fetchLocation($address);
            if ($resolvedLocation !== null) {
                $country = $this->extractCountryName($resolvedLocation);
                $latitude = $resolvedLocation["geometry"]["location"]["lat"];
                $longitude = $resolvedLocation["geometry"]["location"]["lng"];
            }

            // Timezone request.
            if ($latitude !== null && $longitude !== null) {
                $timezone = $this->googleClient->getTimezone($latitude, $longitude);
            }

            // Elevation request.
            if ($latitude !== null && $longitude !== null) {
                $elevation = $this->getElevation($latitude, $longitude);
            }

            $convertedLocation = new Location($country, $latitude, $longitude, $elevation, $timezone);
            $this->distributedCacheClient->set($this->getAddressCacheKey($address), $convertedLocation, self::ADDRESS_CACHE_TTL);

            return $convertedLocation;
        }

        private function fetchLocation(string $address) : mixed {
            $fetchedLocation = $this->googleClient->getLocation($address);
            if ($fetchedLocation === null) {
                $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompt")["alternativeAddress"];
                $alternativeAddress = $this->generativeContentClient->getResponse($prompt, array("address" => $address));
                $fetchedLocation = $this->googleClient->getLocation($alternativeAddress);
            }

            return $fetchedLocation;
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

            $convertedAddress = new Address(AddressType::Other, $address, $address);
            $this->distributedCacheClient->set($this->getLocationCacheKey($latitude, $longitude), $convertedAddress, self::LOCATION_CACHE_TTL);

            return $convertedAddress;
        }

        private function getAddressCacheKey(string $address) : string {
            return sprintf(self::ADDRESS_CACHE_KEY_FORMAT, $address);
        }

        private function getElevationCacheKey(float $latitude, float $longitude) : string {
            return sprintf(self::ELEVATION_CACHE_KEY_FORMAT, round($latitude, 3), round($longitude, 3));
        }

        private function getLocationCacheKey(float $latitude, float $longitude) : string {
            return sprintf(self::LOCATION_CACHE_KEY_FORMAT, round($latitude, 3), round($longitude, 3));
        }
    }
?>