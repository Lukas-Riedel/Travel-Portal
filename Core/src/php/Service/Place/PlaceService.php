<?php
    namespace Core\Service\Place;

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

    class PlaceService {
        
        private const OLD_PLACE_EVENT_TEMPORARY_TABLE = "old_place_event";
        private const MDY_HMS_DATE_TIME_FORMAT = "m/d/Y H:i:s";
        private const LAYOVER_ATTRIBUTE_KEY = "Layover";

        private readonly PlaceMapper $placeMapper;

        private readonly GenerativeContentClient $generativeContentClient;
        private readonly CalendarClient $calendarClient;
        private readonly GoogleClient $googleClient;

        private readonly ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;
        private readonly PhotoService $photoService;

        private readonly GeocodingService $geocodingService;

        private readonly EventPublisher $eventPublisher;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, GenerativeContentClient $generativeContentClient, CalendarClient $calendarClient,
            GoogleClient $googleClient, ConfigurationService $configurationService, CategoryService $categoryService,
            LabelService $labelService, ForecastService $forecastService, PhotoService $photoService, HighlightService $highlightService,
            NoteService $noteService, GeocodingService $geocodingService, EventPublisher $eventPublisher) {
            $this->placeMapper = new PlaceMapper($databaseClient, $configurationService, $categoryService, $labelService, $forecastService,
                $photoService, $highlightService, $noteService);
            $this->generativeContentClient = $generativeContentClient;
            $this->calendarClient = $calendarClient;
            $this->googleClient = $googleClient;
            $this->configurationService = $configurationService;
            $this->categoryService = $categoryService;
            $this->photoService = $photoService;
            $this->geocodingService = $geocodingService;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
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

        public function getRegularPlace(string $placeId) : ?Place {
            $regularPlaces = $this->doGetRegularPlaces($placeId, null, null, null, null, null, null, null, null, null, PlaceIncludedEntity::values(), PlaceSortingStrategy::OldestAscending);
            return count($regularPlaces) === 1 ? $regularPlaces[0] : null;
        }

        public function getRegularPlaces(?string $categoryId, ?string $labelId, ?string $tripId, ?int $year, ?string $albumId, ?string $photoId, ?float $maxQuality, ?int $minStart, ?int $maxEnd, array $includedEntities, PlaceSortingStrategy $placeSortingStrategy) : array {
            return $this->doGetRegularPlaces(null, $categoryId, $labelId, $tripId, $year, $albumId, $photoId, $maxQuality, $minStart, $maxEnd, $includedEntities, $placeSortingStrategy);
        }

        public function getCandidatePlace(string $placeId) : ?Place {
            $candidatePlaces = $this->doGetCandidatePlaces($placeId, null, null, PlaceIncludedEntity::values());
            return count($candidatePlaces) === 1 ? $candidatePlaces[0] : null;
        }

        public function getCandidatePlaces(?string $categoryId, ?string $tripId, ?string $labelId, array $includedEntities) : array {
            return $tripId !== null
                ? $this->doGetCandidatePlacesForTrip($categoryId, $tripId, $includedEntities)
                : $this->doGetCandidatePlaces(null, $categoryId, $labelId, $includedEntities);
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
            });

            if ($wasUpdated) {
                $this->eventPublisher->publish(Event::PlaceUpdated($placeId));
                $this->updatePlaceExcerpt($placeId, $this->getSuggestedExcerpt($name, $place->getCountry()));
            }

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
            $places = $this->getRegularPlaces(null, null, $tripId, null, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timezoneOffset = $this->getTimezoneOffset($date->getStart(), $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $place->getTimezone());
                    $this->googleClient->updateCalendarEventDates(Calendar::Places, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()), $date->getStart() - $timezoneOffset + $offset, $date->getEnd() - $timezoneOffset + $offset);
                }
            }

            return $places;
        }

        public function loadPlaces(string $candidateTripId, int $startOffset) : array {
            $places = $this->doGetCandidatePlacesForTrip(null, $candidateTripId, array());

            foreach ($places as &$place) {
                $address = $this->geocodingService->getFormattedAddress($place->getName(), $place->getPlaceIdentifier()->getLocation());
                foreach ($place->getDates() as &$date) {
                    $this->googleClient->createCalendarEvent(Calendar::Places, $place->getName(), $address, $startOffset + $date->getStart(), $startOffset + $date->getEnd());
                }
            }

            $this->removeCandidateEventsForCandidateTrip($candidateTripId);

            return $places;
        }

        public function archivePlaces(string $tripId, int $tripStart, TripIdentifier $archivedTripIdentifier) : array {
            $places = $this->getRegularPlaces(null, null, $tripId, null, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            
            $this->transactionManager->executeAtomically(function() use(&$places, &$tripStart, &$archivedTripIdentifier) {
                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        $timeOffset = $this->getTimezoneOffset($date->getStart(), $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $place->getTimezone());
                        if ($this->placeMapper->insertPlaceCandidateEvent($place->withUpdatedDates(array(new Date($date->getStart() - $timeOffset - $tripStart, $date->getEnd() - $timeOffset - $tripStart, false, null, null, null, $archivedTripIdentifier))))) {
                            $this->googleClient->deleteCalendarEvent(Calendar::Places, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()));
                        }
                    }
                }
            });
            
            return $this->doGetCandidatePlacesForTrip(null, $archivedTripIdentifier->getId(), array());
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

        public function removePermanentPlace(string $placeId) : bool {
           return $this->removeSpecialPlace(SpecialPlaceType::Permanent, $placeId);
        }

        public function removeCandidatePlace(string $placeId) : bool {
            return $this->removeSpecialPlace(SpecialPlaceType::Candidate, $placeId);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->placeMapper->createPlaceEventTemporaryTable(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            $placeEvents = $this->calendarClient->getEvents(Calendar::Places);

            $this->transactionManager->executeAtomically(function() use(&$placeEvents, &$tripService) {
                $this->placeMapper->deleteAllPlaceEvents();                
                foreach ($placeEvents as &$placeEvent) {
                    $resolvedLocation = $this->geocodingService->getLocation($placeEvent->getLocation());
                    $placeIdentifier = $this->getOrCreatePlaceIdentifier($placeEvent->getSummary(), $resolvedLocation->getCountry(), $placeEvent->getLocation());                        
                    $timeOffset = $this->getTimezoneOffset($placeEvent->getStart(), $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $placeIdentifier->getTimezone());
                    $start = $placeEvent->getStart() + $timeOffset;
                    $end = $placeEvent->getEnd() + $timeOffset;     
                    $isLayover = array_key_exists(self::LAYOVER_ATTRIBUTE_KEY, $placeEvent->getAttributes());
                    $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($start, $end);
                    $place = new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                        $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getScore(), $placeIdentifier->getQuality(),
                        $placeIdentifier->getExcerpt(), array(), array(), array(), array(), array(new Date($start, $end, $isLayover, null, null, null, $resolvedTripIdentifier)));

                    $this->placeMapper->insertPlaceEvent($place, $placeEvent->getId());

                    // Update address to match a common format.
                    $newAddress = $this->geocodingService->getFormattedAddress($placeIdentifier->getName(), $resolvedLocation);
                    if ($this->normalize($placeEvent->getLocation()) !== $this->normalize($newAddress)) {
                        $this->googleClient->updateCalendarEventLocation(Calendar::Places, $placeEvent->getId(), $newAddress);
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

        private function getOrCreatePlaceIdentifier(string $name, string $country, string $address) : PlaceIdentifier {            
            $placeIdentifier = $this->placeMapper->selectPlaceIdentifier($name, $country);
            if ($placeIdentifier !== null) {
                return $placeIdentifier;
            }

            if ($country === array_values(array_filter($this->configurationService->getConfigurationEntry("countryNames"), 
                fn($c) => $c["country"] === "UNKNOWN"))[0]["name"]) {
                throw new \InvalidArgumentException("Cannot create an identifier for an unknown country.");
            }
            
            $location = $this->geocodingService->getLocation($address);
            $placeIdentifier = new PlaceIdentifier(null, $name, $this->categoryService->getOrCreateCountryCategoryIdentifier($country)->getName(),
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

        private function getSuggestedExcerpt(string $name, string $country) : ?string {
            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompts")["placeExcerpt"];
            return $this->generativeContentClient->getResponse($prompt, array("name" => $name, "country" => $country));
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
            $this->transactionManager->executeAtomically(function() use(&$specialPlaceType, &$placeIdentifier) {
                $this->placeMapper->deleteSpecialPlace($specialPlaceType, $placeIdentifier->getId());
                $this->placeMapper->insertSpecialPlace($specialPlaceType, $placeIdentifier->getId());
            });
    
            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getScore(),
                $placeIdentifier->getQuality(), $placeIdentifier->getExcerpt(), array(), array(), array(), array(), array());
        }

        private function doGetRegularPlaces(?string $placeId, ?string $categoryId, ?string $labelId, ?string $tripId, ?int $year, ?string $albumId, ?string $photoId, ?float $maxQuality, ?int $minStart, ?int $maxEnd, array $includedEntities, PlaceSortingStrategy $placeSortingStrategy) : array {
            return $this->placeMapper->selectRegularPlaces($placeId, $categoryId, $labelId, $tripId, $year, $albumId, $photoId, $maxQuality, $minStart, $maxEnd, $includedEntities, $placeSortingStrategy);
        }
        
        private function doGetCandidatePlaces(?string $placeId, ?string $categoryId, ?string $labelId, array $includedEntities) : array {
            return $this->placeMapper->selectCandidatePlaces($placeId, $categoryId, $labelId, $includedEntities);
        }

        private function doGetCandidatePlacesForTrip(?string $categoryId, string $tripId, array $includedEntities) : array {            
            return $this->placeMapper->selectCandidatePlacesForTrip($categoryId, $tripId, $includedEntities);
        }

        public function onPlaceEventCreated(mixed $message) : void {
            $place = $this->getRegularPlace($message["placeId"]);
            if (count($place->getDates()) > 0) {
                $firstDate = $place->getDates()[0];
                if ($firstDate->getStart() > time()) {
                    $this->transactionManager->executeAtomically(function() use(&$place) {
                        $this->placeMapper->deleteSpecialPlace(SpecialPlaceType::Candidate, $place->getId());
                        $this->placeMapper->insertSpecialPlace(SpecialPlaceType::Candidate, $place->getId());
                    });
                }
            }
        }
    }
?>