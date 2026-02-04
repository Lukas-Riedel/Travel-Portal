<?php
    namespace Core\Service\Place;

    use Common\Client\Cache\CacheClient;
    use Core\Client\Calendar\Calendar;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Category\CategoryService;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Label\LabelService;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Forecast\ForecastService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Note\NoteService;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Trip\TripIdentifier;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Client\Calendar\CalendarClient;
    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;
    use Core\Service\Geocoding\Location;

    class PlaceService {
        
        private const OLD_PLACE_EVENT_TEMPORARY_TABLE = "old_place_event";
        private const MDY_HMS_DATE_TIME_FORMAT = "m/d/Y H:i:s";
        private const LAYOVER_ATTRIBUTE_KEY = "Layover";

        private const PLACE_SIGNIFICANCE_CACHE_KEY_FORMAT = "PlaceService:PlaceSignificance:%s";
        private const PLACE_SIGNIFICANCE_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;

        private readonly PlaceMapper $placeMapper;

        private readonly GenerativeContentClient $generativeContentClient;
        
        private readonly CalendarClient $calendarClient;

        private readonly GoogleClient $googleClient;

        private readonly CacheClient $distributedCacheClient;

        private readonly ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;

        private readonly PhotoService $photoService;

        private readonly GeocodingService $geocodingService;

        private readonly EventPublisher $eventPublisher;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, GenerativeContentClient $generativeContentClient, CalendarClient $calendarClient,
            GoogleClient $googleClient, CacheClient $distributedCacheClient, CacheClient $memoryCacheClient, ConfigurationService $configurationService,
            CategoryService $categoryService, LabelService $labelService, ForecastService $forecastService, PhotoService $photoService,
            HighlightService $highlightService, NoteService $noteService, GeocodingService $geocodingService, EventPublisher $eventPublisher) {
            $this->placeMapper = new PlaceMapper($databaseClient, $configurationService, $categoryService, $labelService, $forecastService,
                $photoService, $highlightService, $noteService, $memoryCacheClient);
            $this->generativeContentClient = $generativeContentClient;
            $this->calendarClient = $calendarClient;
            $this->googleClient = $googleClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->configurationService = $configurationService;
            $this->categoryService = $categoryService;
            $this->photoService = $photoService;
            $this->geocodingService = $geocodingService;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }

        public function getPlaceSignificance(string $placeId) : int {
            $cacheKey = sprintf(self::PLACE_SIGNIFICANCE_CACHE_KEY_FORMAT, $placeId);
            $placeSignificance = $this->distributedCacheClient->get($cacheKey);
            if ($placeSignificance !== null) {
                return intval($placeSignificance);
            }
            
            $placeIdentifier = $this->getPlaceIdentifierById($placeId);
            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompts")["placeSignificance"];
            $placeSignificance = intval($this->generativeContentClient->getResponse($prompt, array("name" => $placeIdentifier->getName(), "country" => $placeIdentifier->getCountry() ?? "")));

            $this->distributedCacheClient->set($cacheKey, $placeSignificance, self::PLACE_SIGNIFICANCE_CACHE_TTL);

            return $placeSignificance;
        }

        public function getDatesForTripAndCountry(string $tripId, string $countryCategoryId) : array {
            return $this->placeMapper->selectDatesForTripAndCountry($tripId, $countryCategoryId);
        }

        public function getCountryCategoriesForTrip(string $tripId) : array {
            return $this->placeMapper->selectCountryCategoriesForTrip($tripId);
        }

        public function getCountryCategoriesForCandidateTrip(string $tripId) : array {
            return $this->placeMapper->selectCountryCategoriesForCandidateTrip($tripId);
        }

        public function getVisitedCategoriesForInterval(int $start, int $end, ?CategoryCategory $category, VisitedCategoriesSortingStrategy $visitedCategoriesSortingStrategy) : array {
            return $this->placeMapper->selectVisitedCategoriesForInterval($start, $end, $category, $visitedCategoriesSortingStrategy);
        }

        public function getRegularPlace(string $placeId, ?int $nearbyPlaces = null) : ?Place {
            $regularPlaces = $this->doGetRegularPlaces($placeId, null, null, null, null, null, null, null, null, null, $nearbyPlaces, null, PlaceIncludedEntity::values(), PlaceSortingStrategy::OldestAscending);
            return count($regularPlaces) === 1 ? $regularPlaces[0] : null;
        }

        public function getRegularPlaces(?string $categoryId, ?string $labelId, ?string $tripId, ?int $year, ?string $albumId, ?string $photoId, ?float $maxQuality, ?int $minStart, ?int $maxEnd, ?int $nearbyPlaces, ?int $limit, array $includedEntities, PlaceSortingStrategy $placeSortingStrategy) : array {
            return $this->doGetRegularPlaces(null, $categoryId, $labelId, $tripId, $year, $albumId, $photoId, $maxQuality, $minStart, $maxEnd, $nearbyPlaces, $limit, $includedEntities, $placeSortingStrategy);
        }

        public function getRegularPlaceForAlbum(string $albumId) : ?Place {
            $regularPlaces = $this->doGetRegularPlaces(null, null, null, null, null, $albumId, null, null, null, null, null, null, PlaceIncludedEntity::values(), PlaceSortingStrategy::OldestAscending);
            return count($regularPlaces) === 1 ? $regularPlaces[0] : null;            
        }

        public function getCandidatePlace(string $placeId, ?int $nearbyPlaces = null) : ?Place {
            $candidatePlaces = $this->doGetCandidatePlaces($placeId, null, null, $nearbyPlaces, PlaceIncludedEntity::values());
            return count($candidatePlaces) === 1 ? $candidatePlaces[0] : null;
        }

        public function getCandidatePlaces(?string $categoryId, ?string $tripId, ?string $labelId, ?int $nearbyPlaces, array $includedEntities) : array {
            return $tripId !== null
                ? $this->doGetCandidatePlacesForTrip($categoryId, $tripId, $nearbyPlaces, $includedEntities)
                : $this->doGetCandidatePlaces(null, $categoryId, $labelId, $nearbyPlaces, $includedEntities);
        }

        public function getAllPlaceIdentifiers() : array {
            return $this->placeMapper->selectAllPlaceIdentifiers();
        }

        public function getPlaceIdentifierById(string $placeId) : ?PlaceIdentifier {
            return $this->placeMapper->selectPlaceIdentifierById($placeId);
        }

        public function updatePlaceMainHighlight(string $placeId, string $highlightIdentifier) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$placeId, &$highlightIdentifier, &$wasUpdated) {
                $wasUpdated &= $this->placeMapper->updatePlaceMainHighlight($placeId, $highlightIdentifier);
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::PlaceUpdated($placeId));
                }
            });
            return $wasUpdated;
        }

        public function updatePlaceScore(string $placeId, float $score) : bool {
            return $this->placeMapper->updatePlaceScore($placeId, $score);
        }

        public function updatePlaceQuality(string $placeId, ?float $quality) : bool {
            return $this->placeMapper->updatePlaceQuality($placeId, $quality);
        }

        public function updatePlaceExcerpt(string $placeId, ?string $excerpt) : bool {
            if ($excerpt === null) {
                $placeIdentifier = $this->getPlaceIdentifierById($placeId);
                $excerpt = $this->getSuggestedExcerpt($placeIdentifier->getName(), $placeIdentifier->getCountry());
            }
            return $this->placeMapper->updatePlaceExcerpt($placeId, $excerpt);
        }

        public function updatePlaceName(string $placeId, string $name) : bool {
            $place = $this->getRegularPlace($placeId);
            if ($place === null) {
                $place = $this->getCandidatePlace($placeId);
            }

            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$placeId, &$name, &$place, &$wasUpdated) {
                $wasUpdated &= $this->placeMapper->updatePlaceName($placeId, $name);

                if ($place !== null) {
                    foreach ($place->getDates() as &$date) {                       
                        $album = $date->getAlbum();
                        if ($album !== null) {     
                            $wasUpdated &= $this->photoService->updateAlbumName($album->getId(), $place->getName(), $name);
                        }

                        $eventId = $this->placeMapper->selectPlaceEventId($placeId, $date->getStart());
                        if ($eventId !== null) {  
                            $wasUpdated &= $this->googleClient->updateCalendarEventName(Calendar::Places, $eventId, $name);
                        }
                    }
                }
                else {
                    $wasUpdated = false;
                }
            });

            if ($wasUpdated) {
                $this->eventPublisher->publish(Event::PlaceUpdated($placeId));
                $this->updatePlaceExcerpt($placeId, $this->getSuggestedExcerpt($name, $place->getCountry()));
            }

            return $wasUpdated;
        }

        public function updatePlaceCountry(string $placeId, string $country) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$placeId, &$country, &$wasUpdated) {
                $wasUpdated &= $this->placeMapper->updatePlaceCountry($placeId, $country);            
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::PlaceUpdated($placeId));
                }
            });
            return $wasUpdated;
        }

        public function updatePlaceLocation(string $placeId, float $latitude, float $longitude) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$placeId, &$latitude, &$longitude, &$wasUpdated) {
                $wasUpdated &= $this->placeMapper->updatePlaceLocation($placeId, $latitude, $longitude);            
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::PlaceUpdated($placeId));
                }
            });
            return $wasUpdated;
        }

        public function movePlaces(string $tripId, int $offset) : array {
            $places = $this->getRegularPlaces(null, null, $tripId, null, null, null, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $this->googleClient->updateCalendarEventStartEnd(Calendar::Places, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()),
                        $date->getStart() + $offset, $date->getEnd() + $offset, $place->getTimezone(), $place->getTimezone());
                }
            }

            return $places;
        }

        public function loadPlaces(string $candidateTripId, int $startOffset) : array {
            $places = $this->doGetCandidatePlacesForTrip(null, $candidateTripId, null, array(PlaceIncludedEntity::Dates->value));

            foreach ($places as &$place) {
                $address = $this->geocodingService->getFormattedAddress($place->getName(), $place->getPlaceIdentifier()->getLocation());
                foreach ($place->getDates() as &$date) {
                    $this->googleClient->createCalendarEvent(Calendar::Places, $place->getName(), $address, $startOffset + $date->getStart(), $startOffset + $date->getEnd(), $place->getTimezone(), $place->getTimezone());
                }
            }

            $this->removeCandidateEventsForCandidateTrip($candidateTripId);

            return $places;
        }

        public function archivePlaces(string $tripId, int $tripStart, TripIdentifier $archivedTripIdentifier) : array {
            $places = $this->getRegularPlaces(null, null, $tripId, null, null, null, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            
            $this->transactionManager->executeAtomically(function() use(&$places, &$tripStart, &$archivedTripIdentifier) {
                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        if ($this->placeMapper->insertPlaceCandidateEvent($place->withUpdatedDates(array(new Date($date->getStart() - $tripStart, $date->getEnd() - $tripStart, false, null, null, null, $archivedTripIdentifier))))) {
                            $this->googleClient->deleteCalendarEvent(Calendar::Places, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()));
                        }
                    }
                }
            });
            
            return $this->doGetCandidatePlacesForTrip(null, $archivedTripIdentifier->getId(), null, array());
        }
        
        public function removeCandidateEventsForCandidateTrip(string $tripId) : bool {
            $wasRemoved = $this->placeMapper->deleteCandidateEventsForCandidateTrip($tripId) > 0;
            if ($wasRemoved) {
                $this->placeMapper->deleteStalePlaceIdentifiers();
            }
            return $wasRemoved;
        }

        public function createPermanentPlace(string $name, string $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Permanent, $name, $address);
        }

        public function createCandidatePlace(string $name, string $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Candidate, $name, $address);
        }

        public function getOrCreateCandidatePlace(PlaceIdentifier $placeIdentifier) : Place {
            return $this->getOrCreateSpecialPlace(SpecialPlaceType::Candidate, $placeIdentifier);
        }

        public function removePermanentPlace(string $placeId) : bool {
           return $this->removeSpecialPlace(SpecialPlaceType::Permanent, $placeId);
        }

        public function removeCandidatePlace(string $placeId) : bool {
            return $this->removeSpecialPlace(SpecialPlaceType::Candidate, $placeId);
        }

        public function refreshPlaceEventLocation(string $placeId, int $start) : bool {
            $placeIdentifier = $this->getPlaceIdentifierById($placeId);
            if ($placeIdentifier === null) {
                return false;
            }

            $eventId = $this->placeMapper->selectPlaceEventId($placeId, $start);
            if ($eventId === null) {
                return false;
            }
            
            $resolvedLocation = new Location($placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone());
            $newAddress = $this->geocodingService->getFormattedAddress($placeIdentifier->getName(), $resolvedLocation);
            
            return $this->googleClient->updateCalendarEventLocation(Calendar::Places, $eventId, $newAddress);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->placeMapper->createPlaceEventTemporaryTable(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            $placeEvents = $this->calendarClient->getEvents(Calendar::Places);

            $this->transactionManager->executeAtomically(function() use(&$placeEvents, &$tripService) {
                $this->placeMapper->deleteAllPlaceEvents();                
                foreach ($placeEvents as &$placeEvent) {
                    $resolvedLocation = $this->geocodingService->getLocation($placeEvent->getLocation());
                    $placeIdentifier = $this->getOrCreatePlaceIdentifier($placeEvent->getSummary(), $resolvedLocation->getCountry(), $placeEvent->getLocation());
                    $start = $placeEvent->getStart();
                    $end = $placeEvent->getEnd();
                    
                    // The time is considered normalized if the event timezone is the same as the place timezone.
                    $isTimeNormalized = !$placeEvent->shouldBeNormalized($placeIdentifier->getTimezone(), $placeIdentifier->getTimezone());
                                        
                    if (!$isTimeNormalized) {
                        // TODO: For the offset computation, should we prioritize the timezone from the event? Like '$placeEvent->getStartTimezone() ?? $homeTimezone'.
                        // This should ensure that creating an event on a device in a different timezone will not cause any discrepancies, but it should be verified.
                        $timeOffset = $this->getTimezoneOffset($start, $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $placeIdentifier->getTimezone());
                        $start += $timeOffset;
                        $end += $timeOffset;
                    }
                    
                    $isLayover = array_key_exists(self::LAYOVER_ATTRIBUTE_KEY, $placeEvent->getAttributes());
                    $resolvedTripIdentifier = $tripService->getTripIdentifierForEntity($start, $end);
                    $place = new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                        $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getScore(), $placeIdentifier->getQuality(),
                        $placeIdentifier->getExcerpt(), array(), array(), array(), array(), array(), array(new Date($start, $end, $isLayover, null, null, null, $resolvedTripIdentifier)));

                    $this->placeMapper->insertPlaceEvent($place, $placeEvent->getId());

                    // Update address to match the common format.
                    $newAddress = $this->geocodingService->getFormattedAddress($placeIdentifier->getName(), $resolvedLocation);
                    if ($this->normalize($placeEvent->getLocation()) !== $this->normalize($newAddress)) {
                        $this->googleClient->updateCalendarEventLocation(Calendar::Places, $placeEvent->getId(), $newAddress);
                    }

                    if (!$isTimeNormalized) {
                        $this->googleClient->updateCalendarEventStartEnd(Calendar::Places, $placeEvent->getId(), $start, $end,
                            $placeIdentifier->getTimezone(), $placeIdentifier->getTimezone());
                    }
                }
            });
                
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForCreatedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publish(Event::PlaceCreated($affectedPlaceId));
                $this->eventPublisher->publish(Event::PlaceEventCreated($affectedPlaceId));
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForUpdatedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publish(Event::PlaceUpdated($affectedPlaceId));
                $this->eventPublisher->publish(Event::PlaceEventUpdated($affectedPlaceId));
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForDeletedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publish(Event::PlaceRemoved($affectedPlaceId));
                $this->eventPublisher->publish(Event::PlaceEventRemoved($affectedPlaceId));
            }

            $this->placeMapper->deleteStalePlaceIdentifiers();
            $this->placeMapper->deleteVisitedCandidatePlaces();
        }

        private function getOrCreatePlaceIdentifier(string $name, ?string $country, string $address) : PlaceIdentifier {            
            $placeIdentifier = $this->placeMapper->selectPlaceIdentifier($name, $country);
            if ($placeIdentifier !== null) {
                return $placeIdentifier;
            }

            $location = $this->geocodingService->getLocation($address);
            $placeIdentifier = new PlaceIdentifier(null, $name, $country === null ? null : $this->categoryService->getOrCreateCountryCategoryIdentifier($country)->getName(),
                $location->getLatitude(), $location->getLongitude(), $location->getTimezone(), null, 0, null, $this->getSuggestedExcerpt($name, $country));
            $this->transactionManager->executeAtomically(function() use(&$placeIdentifier) {
                $this->placeMapper->insertPlaceIdentifier($placeIdentifier);
                
                $this->eventPublisher->publish(Event::PlaceCreated($placeIdentifier->getId()));
            });

            return $placeIdentifier;
        }

        private function removeSpecialPlace(SpecialPlaceType $specialPlaceType, string $placeId) : bool {
            $wasRemoved = true;
            $this->transactionManager->executeAtomically(function() use(&$specialPlaceType, &$placeId, &$wasRemoved) {
                $wasRemoved &= $this->placeMapper->deleteSpecialPlace($specialPlaceType, $placeId);

                if ($wasRemoved) {
                    $this->eventPublisher->publish(Event::PlaceRemoved($placeId));
                }
            });

            if ($wasRemoved) {
                $this->placeMapper->deleteStalePlaceIdentifiers();
            }
            return $wasRemoved;
        }

        private function getSuggestedExcerpt(string $name, ?string $country) : ?string {
            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompts")["placeExcerpt"];
            return $this->generativeContentClient->getResponse($prompt, array("name" => $name, "country" => $country ?? ""));
        }

        private function getTimezoneOffset(int $timestamp, string $fromTimezone, string $toTimezone) : int {
            $timezone = new \DateTimeZone($fromTimezone);
            $dateTimeHome = new \DateTime(date(self::MDY_HMS_DATE_TIME_FORMAT, $timestamp), new \DateTimeZone($toTimezone));
            return $timezone->getOffset($dateTimeHome) - (new \DateTimeZone($toTimezone))->getOffset($dateTimeHome);
        }

        private function normalize(?string $str) {
            return $str === null ? null : str_replace(" ", "", $str);
        }

        private function createSpecialPlace(SpecialPlaceType $specialPlaceType, string $name, string $address) : Place {            
            $country = $this->geocodingService->getLocation($address)->getCountry();
            $placeIdentifier = $this->getOrCreatePlaceIdentifier($name, $country, $address);

            $this->placeMapper->insertSpecialPlace($specialPlaceType, $placeIdentifier->getId());
    
            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getScore(),
                $placeIdentifier->getQuality(), $placeIdentifier->getExcerpt(), array(), array(), array(), array(), array(), array());
        }
        

        private function getOrCreateSpecialPlace(SpecialPlaceType $specialPlaceType, PlaceIdentifier $placeIdentifier) : Place {
            $this->transactionManager->executeAtomically(function() use(&$specialPlaceType, &$placeIdentifier) {
                $this->placeMapper->deleteSpecialPlace($specialPlaceType, $placeIdentifier->getId());
                $this->placeMapper->insertSpecialPlace($specialPlaceType, $placeIdentifier->getId());
            });

            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getScore(),
                $placeIdentifier->getQuality(), $placeIdentifier->getExcerpt(), array(), array(), array(), array(), array(), array());
        }

        private function doGetRegularPlaces(?string $placeId, ?string $categoryId, ?string $labelId, ?string $tripId, ?int $year, ?string $albumId, ?string $photoId, ?float $maxQuality, ?int $minStart, ?int $maxEnd, ?int $nearbyPlaces, ?int $limit, array $includedEntities, PlaceSortingStrategy $placeSortingStrategy) : array {
            return $this->placeMapper->selectRegularPlaces($placeId, $categoryId, $labelId, $tripId, $year, $albumId, $photoId, $maxQuality, $minStart, $maxEnd, $nearbyPlaces, $limit, $includedEntities, $placeSortingStrategy);
        }
        
        private function doGetCandidatePlaces(?string $placeId, ?string $categoryId, ?string $labelId, ?int $nearbyPlaces, array $includedEntities) : array {
            return $this->placeMapper->selectCandidatePlaces($placeId, $categoryId, $labelId, $nearbyPlaces, $includedEntities);
        }

        private function doGetCandidatePlacesForTrip(?string $categoryId, string $tripId, ?int $nearbyPlaces, array $includedEntities) : array {            
            return $this->placeMapper->selectCandidatePlacesForTrip($categoryId, $tripId, $nearbyPlaces, $includedEntities);
        }
    }
?>