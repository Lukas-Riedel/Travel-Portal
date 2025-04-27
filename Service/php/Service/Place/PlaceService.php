<?php
    namespace Service\Service\Place;

    use Service\Service\Category\CategoryService;
    use Service\Service\Label\LabelService;
    use Service\Service\Geocoding\GeocodingService;
    use Service\Service\Forecast\ForecastService;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Photo\PhotoService;
    use Service\Service\Statistics\Statistics;
    use Service\Service\Statistics\StatisticsKind;
    use Service\Service\Statistics\StatisticsProvider;
    use Service\Service\Statistics\StatisticsType;
    use Service\Service\Statistics\StatisticsUnit;
    use Service\Service\Trip\TripIdentifier;
    use Service\Service\Trip\TripService;

    class PlaceService implements StatisticsProvider {
        
        private const OLD_PLACE_EVENT_TEMPORARY_TABLE = "old_place_event";
        private const MDY_HMS_DATE_TIME_FORMAT = "m/d/Y H:i:s";
        private const LAYOVER_ATTRIBUTE_KEY = "Layover";

        private const TOTAL_VISITED_COUNTRIES_COUNT_STATISTICS_NAME = "TOTAL_VISITED_COUNTRIES_COUNT";
        private const TOTAL_VISITED_PLACES_COUNT_STATISTICS_NAME = "TOTAL_VISITED_PLACES_COUNT";
        private const FURTHEST_PLACES_STATISTICS_NAME = "FURTHEST_PLACES";
        private const FURTHEST_COUNTRIES_STATISTICS_NAME = "FURTHEST_COUNTRIES";
        private const VISITED_PLACES_PER_COUNTRY_STATISTICS_NAME = "VISITED_PLACES_PER_COUNTRY";
        private const VISITED_PLACES_PER_CATEGORY_STATISTICS_NAME = "VISITED_PLACES_PER_CATEGORY";
        private const WESTERNMOST_PLACES_STATISTICS_NAME = "WESTERNMOST_PLACES";
        private const EASTERNMOST_PLACES_STATISTICS_NAME = "EASTERNMOST_PLACES";
        private const NORTHERNMOST_PLACES_STATISTICS_NAME = "NORTHERNMOST_PLACES";
        private const SOUTHERNMOST_PLACES_STATISTICS_NAME = "SOUTHERNMOST_PLACES";
        private const LEAST_RECENTLY_VISITED_PLACES_STATISTICS_NAME = "LEAST_RECENTLY_VISITED_PLACES";
        private const TOTAL_TRAVEL_DAYS_COUNT_STATISTICS_NAME = "TOTAL_TRAVEL_DAYS_COUNT";
        private const TOTAL_TRAVEL_DAYS_PER_COUNTRY_STATISTICS_NAME = "TOTAL_TRAVEL_DAYS_PER_COUNTRY";
        private const LAST_VISIT_STATISTICS_NAME = "LAST_VISIT";
        private const MOST_VISITED_PLACES_STATISTICS_NAME = "MOST_VISITED_PLACES";
        
        private readonly PlaceMapper $placeMapper;

        private readonly \ChatClient $chatClient;
        private readonly \CalendarClient $calendarClient;
        private readonly \GoogleApiClient $googleApiClient;

        private readonly \ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;
        private readonly PhotoService $photoService;

        private readonly GeocodingService $geocodingService;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \ChatClient $chatClient, \CalendarClient $calendarClient,
            \GoogleApiClient $googleApiClient, \ConfigurationService $configurationService, CategoryService $categoryService,
            LabelService $labelService, ForecastService $forecastService, PhotoService $photoService, HighlightService $highlightService,
            GeocodingService $geocodingService, \EventPublisher $eventPublisher) {
            $this->placeMapper = new PlaceMapper($databaseProvider, $configurationService, $categoryService, $labelService, $forecastService,
                $photoService, $highlightService, $geocodingService);
            $this->chatClient = $chatClient;
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->categoryService = $categoryService;
            $this->photoService = $photoService;
            $this->geocodingService = $geocodingService;
            $this->eventPublisher = $eventPublisher;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Category) {
                    $totalTravelDaysCount = $this->placeMapper->selectTotalTravelDaysCount($start, $end, $categoryId);
                    if ($totalTravelDaysCount > 0) {
                        $statistics[] = new Statistics(self::TOTAL_TRAVEL_DAYS_COUNT_STATISTICS_NAME, $totalTravelDaysCount, StatisticsUnit::Days);
                    }
                }

                $visitedPlacesCount = $this->placeMapper->selectVisitedPlacesCount($start, $end, $categoryId);
                if ($visitedPlacesCount > 0) {
                    $statistics[] = new Statistics(self::TOTAL_VISITED_PLACES_COUNT_STATISTICS_NAME, $visitedPlacesCount, StatisticsUnit::Places);
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $visitedCountriesCount = $this->placeMapper->selectVisitedCountriesCount($start, $end);
                    if ($visitedCountriesCount > 0) {
                        $statistics[] = new Statistics(self::TOTAL_VISITED_COUNTRIES_COUNT_STATISTICS_NAME, $visitedCountriesCount, StatisticsUnit::Countries);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Category) {
                    $lastVisit = $this->placeMapper->selectLastVisit($start, $end, $categoryId);
                    if ($lastVisit > 0) {
                        $statistics[] = new Statistics(self::LAST_VISIT_STATISTICS_NAME, $lastVisit, StatisticsUnit::BeforeDaysTimestamp);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {  
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $travelDaysCountByCountry = $this->placeMapper->selectTotalTravelDaysCountByCountry($start, $end);
                    if (count($travelDaysCountByCountry) > 0) {
                        $statistics[] = new Statistics(self::TOTAL_TRAVEL_DAYS_PER_COUNTRY_STATISTICS_NAME, $travelDaysCountByCountry, StatisticsUnit::Days);
                    }

                    $visitedPlacesCountByCountry = $this->placeMapper->selectVisitedPlacesCountByCountry($start, $end);
                    if (count($visitedPlacesCountByCountry) > 0) {
                        $statistics[] = new Statistics(self::VISITED_PLACES_PER_COUNTRY_STATISTICS_NAME, $visitedPlacesCountByCountry, StatisticsUnit::Places);
                    }
                    
                    $visitedPlacesCountByCategory = $this->placeMapper->selectVisitedPlacesCountByCategory($start, $end);
                    if (count($visitedPlacesCountByCategory) > 0) {
                        $statistics[] = new Statistics(self::VISITED_PLACES_PER_CATEGORY_STATISTICS_NAME, $visitedPlacesCountByCategory, StatisticsUnit::Places);
                    }
                    
                    $furthestPlaces = $this->placeMapper->selectFurthestPlaces($start, $end);
                    if (count($furthestPlaces) > 0) {
                        $statistics[] = new Statistics(self::FURTHEST_PLACES_STATISTICS_NAME, $furthestPlaces, StatisticsUnit::Kilometers);
                    }
                    
                    $furthestCountries = $this->placeMapper->selectFurthestCountries($start, $end);
                    if (count($furthestCountries) > 0) {
                        $statistics[] = new Statistics(self::FURTHEST_COUNTRIES_STATISTICS_NAME, $furthestCountries, StatisticsUnit::Kilometers);
                    }
                    
                    $northernmostCountries = $this->placeMapper->selectNorthernmostPlaces($start, $end);
                    if (count($northernmostCountries) > 0) {
                        $statistics[] = new Statistics(self::NORTHERNMOST_PLACES_STATISTICS_NAME, $northernmostCountries, StatisticsUnit::Kilometers);
                    }

                    $southernmostCountries = $this->placeMapper->selectSouthernmostPlaces($start, $end);
                    if (count($southernmostCountries) > 0) {
                        $statistics[] = new Statistics(self::SOUTHERNMOST_PLACES_STATISTICS_NAME, $southernmostCountries, StatisticsUnit::Kilometers);
                    }

                    $easternmostCountries = $this->placeMapper->selectEasternmostPlaces($start, $end);
                    if (count($easternmostCountries) > 0) {
                        $statistics[] = new Statistics(self::EASTERNMOST_PLACES_STATISTICS_NAME, $easternmostCountries, StatisticsUnit::Kilometers);
                    }

                    $westernmostCountries = $this->placeMapper->selectWesternmostPlaces($start, $end);
                    if (count($westernmostCountries) > 0) {
                        $statistics[] = new Statistics(self::WESTERNMOST_PLACES_STATISTICS_NAME, $westernmostCountries, StatisticsUnit::Kilometers);
                    }
                }
                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Category) {
                    $leastRecentlyVisitedPlaces = $this->placeMapper->selectLeastRecentlyVisitedPlaces($start, $end, $categoryId);
                    if (count($leastRecentlyVisitedPlaces) > 0) {
                        $statistics[] = new Statistics(self::LEAST_RECENTLY_VISITED_PLACES_STATISTICS_NAME, $leastRecentlyVisitedPlaces, StatisticsUnit::BeforeDaysTimestamp);
                    }
                }
                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Category) {
                    $mostVisitedPlaces = $this->placeMapper->selectMostVisitedPlaces($start, $end, $categoryId);
                    if (count($mostVisitedPlaces) > 0) {
                        $statistics[] = new Statistics(self::MOST_VISITED_PLACES_STATISTICS_NAME, $mostVisitedPlaces, StatisticsUnit::Visits);
                    }
                }
            }

            return $statistics;
        }

        public function getDatesForTripAndCountry(string $tripId, string $country) : array {
            return $this->placeMapper->selectDatesForTripAndCountry($tripId, $country);
        }

        public function getCountriesForTrip(string $tripId) : array {
            return $this->placeMapper->selectCountriesForTrip($tripId);
        }

        public function getCountriesForCandidateTrip(string $tripId) : array {
            return $this->placeMapper->selectCountriesForCandidateTrip($tripId);
        }

        public function getDaysForCandidateTrip(string $tripId) : int {
            return $this->placeMapper->selectDaysForCandidateTrip($tripId);
        }

        public function getRegularPlace(string $placeId) : ?Place {
            $regularPlaces = $this->doGetRegularPlaces($placeId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, PlaceIncludedEntity::values());
            return count($regularPlaces) === 1 ? $regularPlaces[0] : NULL;
        }

        public function getRegularPlaces(?string $categoryId, ?string $label, ?string $tripId, ?int $year, ?string $albumId, ?int $minStart, ?int $maxEnd, array $includedEntities) : array {
            return $this->doGetRegularPlaces(NULL, $categoryId, $label, $tripId, $year, $albumId, $minStart, $maxEnd, $includedEntities);
        }

        public function getCandidatePlace(string $placeId) : ?Place {
            $candidatePlaces = $this->doGetCandidatePlaces($placeId, NULL, PlaceIncludedEntity::values());
            return count($candidatePlaces) === 1 ? $candidatePlaces[0] : NULL;
        }

        public function getCandidatePlaces(?string $categoryId, ?string $tripId, array $includedEntities) : array {
            return $tripId !== NULL
                ? $this->doGetCandidatePlacesForTrip($categoryId, $tripId, $includedEntities)
                : $this->doGetCandidatePlaces(NULL, $categoryId, $includedEntities);
        }

        public function getPlaceIdentifierById(string $placeId) : ?PlaceIdentifier {
            return $this->placeMapper->selectPlaceIdentifierById($placeId);
        }

        public function updatePlaceMainHighlight(string $placeId, string $highlightIdentifier) : bool {
            return $this->placeMapper->updatePlaceMainHighlight($placeId, $highlightIdentifier);
        }

        public function updatePlaceExcerpt(string $placeId, string $excerpt) : bool {
            return $this->placeMapper->updatePlaceExcerpt($placeId, $excerpt);
        }

        public function updatePlaceName(string $placeId, string $name) : bool {
            $place = $this->getRegularPlace($placeId);
            $wasUpdated = $this->placeMapper->updatePlaceName($placeId, $name);

            if ($place !== NULL) {
                foreach ($place->getDates() as &$date) {                       
                    $album = $date->getAlbum();
                    if ($album !== NULL) {     
                        $wasUpdated &= $this->photoService->updateAlbumName($album->getId(), $place->getName(), $name);
                    }

                    $eventId = $this->placeMapper->selectPlaceEventId($placeId, $date->getStart());
                    if ($eventId !== NULL) {  
                        $wasUpdated &= $this->googleApiClient->updateCalendarEventSummary(\Calendar::Places->value, $eventId, $name);
                    }
                }
            }

            if ($wasUpdated) {
                $this->eventPublisher->publishPlaceUpdatedEvent($this->getPlaceIdentifierById($placeId));
                $this->updatePlaceExcerpt($placeId, $this->getSuggestedExcerpt($name, $place->getCountry()));
            }

            return $wasUpdated;
        }

        public function updatePlaceLocation(string $placeId, float $latitude, float $longitude) : bool {
            $wasUpdated = $this->placeMapper->updatePlaceLocation($placeId, $latitude, $longitude);
            
            if ($wasUpdated) {
                $this->eventPublisher->publishPlaceUpdatedEvent($this->getPlaceIdentifierById($placeId));
            }

            return $wasUpdated;
        }

        public function movePlaces(string $tripId, int $offset) : array {
            $places = $this->getRegularPlaces(NULL, NULL, $tripId, NULL, NULL, NULL, NULL, array(PlaceIncludedEntity::Dates->value));

            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timezoneOffset = $this->getTimezoneOffset($date->getStart(), $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "timezone"), $place->getTimezone());
                    $this->googleApiClient->updateCalendarEventDates(\Calendar::Places->value, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()), $date->getStart() - $timezoneOffset + $offset, $date->getEnd() - $timezoneOffset + $offset);
                }
            }

            return $places;
        }

        public function loadPlaces(string $candidateTripId, int $startOffset) : array {
            $places = $this->doGetCandidatePlacesForTrip(NULL, $candidateTripId, array());

            $allCalendarEventsCreated = TRUE;
            foreach ($places as &$place) {
                $address = $this->geocodingService->getAddress($place->getName(), $place->getPlaceIdentifier()->getLocation());
                foreach ($place->getDates() as &$date) {
                    $allCalendarEventsCreated &= $this->googleApiClient->createCalendarEvent(\Calendar::Places->value, $place->getName(), $address, $startOffset + $date->getStart(), $startOffset + $date->getEnd());
                }
            }

            if ($allCalendarEventsCreated) {
                $this->removeCandidateEventsForCandidateTrip($candidateTripId);
            }

            return $places;
        }

        public function archivePlaces(string $tripId, int $tripStart, TripIdentifier $archivedTripIdentifier) : array {
            $places = $this->getRegularPlaces(NULL, NULL, $tripId, NULL, NULL, NULL, NULL, array(PlaceIncludedEntity::Dates->value));
            
            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timeOffset = $this->getTimezoneOffset($date->getStart(), $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "timezone"), $place->getTimezone());
                    if ($this->placeMapper->insertPlaceCandidateEvent($place->withUpdatedDates(array(new Date($date->getStart() - $timeOffset - $tripStart, $date->getEnd() - $timeOffset - $tripStart, FALSE, NULL, NULL, NULL, $archivedTripIdentifier))))) {
                        $this->googleApiClient->deleteCalendarEvent(\Calendar::Places->value, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()));
                    }
                }
            }
            
            return $this->doGetCandidatePlacesForTrip(NULL, $archivedTripIdentifier->getId(), array());
        }
        
        public function removeCandidateEventsForCandidateTrip(string $tripId) : bool {
            return $this->placeMapper->deleteCandidateEventsForCandidateTrip($tripId) > 0;
        }

        public function createPermanentPlace(string $name, string $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Permanent, $name, $address);
        }

        public function createCandidatePlace(string $name, string $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Candidate, $name, $address);
        }

        public function removePermanentPlace(string $placeId) : bool {
           return $this->removeSpecialPlace(SpecialPlaceType::Permanent, $placeId);
        }

        public function removeCandidatePlace(string $placeId) : bool {
            return $this->removeSpecialPlace(SpecialPlaceType::Candidate, $placeId);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->placeMapper->createPlaceEventTemporaryTable(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            $this->placeMapper->deleteAllPlaceEvents();
                
            foreach ($this->calendarClient->getEvents(\Calendar::Places->value) as &$placeEvent) {
                $resolvedLocation = $this->geocodingService->getLocation($placeEvent->getLocation());
                $placeIdentifier = $this->getOrCreatePlaceIdentifier($placeEvent->getSummary(), $resolvedLocation->getCountry(), $placeEvent->getLocation());                        
                $timeOffset = $this->getTimezoneOffset($placeEvent->getStart(), $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "timezone"), $placeIdentifier->getTimezone());
                $start = $placeEvent->getStart() + $timeOffset;
                $end = $placeEvent->getEnd() + $timeOffset;     
                $isLayover = array_key_exists(self::LAYOVER_ATTRIBUTE_KEY, $placeEvent->getAttributes());
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($start, $end);
                $place = new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                    $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getExcerpt(),
                    array(), array(), array(), array(new Date($start, $end, $isLayover, NULL, NULL, NULL, $resolvedTripIdentifier)));

                $this->placeMapper->insertPlaceEvent($place, $placeEvent->getId());

                // Update address to match a common format.
                $newAddress = $this->geocodingService->getAddress($placeIdentifier->getName(), $resolvedLocation);
                if ($this->normalize($placeEvent->getLocation()) !== $this->normalize($newAddress)) {
                    $this->googleApiClient->updateCalendarEventLocation(\Calendar::Places->value, $placeEvent->getId(), $newAddress);
                }
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForCreatedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publishPlaceEventCreatedEvent($affectedPlaceId);
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForUpdatedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publishPlaceEventUpdatedEvent($affectedPlaceId);
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForDeletedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publishPlaceEventDeletedEvent($affectedPlaceId);
            }
        }

        private function getOrCreatePlaceIdentifier(string $name, string $country, string $address) : PlaceIdentifier {            
            $placeIdentifier = $this->placeMapper->selectPlaceIdentifier($name, $country);
            if ($placeIdentifier !== NULL) {
                return $placeIdentifier;
            }

            if ($country === $this->configurationService->getConfigurationForTypeAndKey("countryNames", "UNKNOWN")) {
                throw new \InvalidArgumentException("Cannot create an identifier for an unknown country.");
            }
            
            $location = $this->geocodingService->getLocation($address);
            $placeIdentifier = new PlaceIdentifier(NULL, $name, $this->categoryService->getOrCreateCountryCategoryIdentifier($country)->getId(),
                $location->getLatitude(), $location->getLongitude(), $location->getTimezone(), NULL, $this->getSuggestedExcerpt($name, $country));
            $this->placeMapper->insertPlaceIdentifier($placeIdentifier);

            return $placeIdentifier;
        }

        private function removeSpecialPlace(SpecialPlaceType $specialPlaceType, string $placeId) : bool {
            $wasRemoved = $this->placeMapper->deleteSpecialPlace($specialPlaceType, $placeId);

            if ($wasRemoved) {
                $this->eventPublisher->publishPlaceDeletedEvent($this->getPlaceIdentifierById($placeId));
            }

            return $wasRemoved;
        }

        private function getSuggestedExcerpt(string $name, string $country) : ?string {
            return $this->chatClient->getResponse(sprintf($this->configurationService->getConfigurationForTypeAndKey("chatRequests", "suggestedExcerpt"), $name, $country));
        }

        private function getTimezoneOffset($timestamp, $fromTimezone, $toTimezone) : int {
            $timezone = new \DateTimeZone($fromTimezone);
            $dateTimeHome = new \DateTime(date(self::MDY_HMS_DATE_TIME_FORMAT, $timestamp), new \DateTimeZone($toTimezone));
            return $timezone->getOffset($dateTimeHome) - (new \DateTimeZone($toTimezone))->getOffset($dateTimeHome);
        }

        private function normalize(string $string) {
            return str_replace(" ", "", $string);
        }

        private function createSpecialPlace(SpecialPlaceType $specialPlaceType, string $name, string $address) : Place {            
            $country = $this->geocodingService->getLocation($address)->getCountry();
            $placeIdentifier = $this->getOrCreatePlaceIdentifier($name, $country, $address);

            // TODO: Remove the create-if-not-exists semantics.
            $this->placeMapper->deleteSpecialPlace($specialPlaceType, $placeIdentifier->getId());
            $this->placeMapper->insertSpecialPlace($specialPlaceType, $placeIdentifier->getId());
    
            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getExcerpt(), array(), array(), array(), array());
        }

        private function doGetRegularPlaces(?string $placeId, ?string $categoryId, ?string $label, ?string $tripId, ?int $year, ?string $albumId, ?int $minStart, ?int $maxEnd, array $includedEntities) : array {
            return $this->placeMapper->selectRegularPlaces($placeId, $categoryId, $label, $tripId, $year, $albumId, $minStart, $maxEnd, $includedEntities);
        }
        
        private function doGetCandidatePlaces(?string $placeId, ?string $categoryId, array $includedEntities) : array {
            return $this->placeMapper->selectCandidatePlaces($placeId, $categoryId, $includedEntities);
        }

        private function doGetCandidatePlacesForTrip(?string $categoryId, string $tripId, array $includedEntities) : array {            
            return $this->placeMapper->selectCandidatePlacesForTrip($categoryId, $tripId, $includedEntities);
        }

        public function onPlaceEventCreated(mixed $message) : void {
            $place = $this->getRegularPlace($message["placeId"]);
            if (count($place->getDates()) > 0) {
                $firstDate = $place->getDates()[0];
                if ($firstDate->getStart() > time()) {
                    $this->placeMapper->deleteSpecialPlace(SpecialPlaceType::Candidate, $place->getId());
                    $this->placeMapper->insertSpecialPlace(SpecialPlaceType::Candidate, $place->getId());
                }
            }
        }
    }
?>