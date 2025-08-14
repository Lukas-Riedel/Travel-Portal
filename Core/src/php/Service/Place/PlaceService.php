<?php
    namespace Core\Service\Place;

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

    class PlaceService {
        
        private const OLD_PLACE_EVENT_TEMPORARY_TABLE = "old_place_event";
        private const MDY_HMS_DATE_TIME_FORMAT = "m/d/Y H:i:s";
        private const LAYOVER_ATTRIBUTE_KEY = "Layover";

        private const GET_SUGGESTED_EXCERPT_CHAT_PROMPT_FORMAT = "Please write an article about the place %s (%s) for my travel blog. The article will be published without editing, so it must be well-written, accurate, engaging, and factually rich. Focus on a general description of the place, covering its history, geographical location, cultural and natural highlights, economic and social importance, and any unique features that make it stand out. If it is a city or larger town, also include basic facts such as the population, climate, or interesting details about transportation. Avoid listing specific tourist attractions and marketing phrases like \"taste the local specialties.\" The text should be around 200 to 400 words depending on the significance of the place. Write it as a single paragraph without headings, emojis, or direct calls to action. The article should provide the reader with a valuable, factual, and comprehensive overview that sparks curiosity to learn more.";
        
        private readonly PlaceMapper $placeMapper;

        private readonly \ChatClient $chatClient;
        private readonly \CalendarClient $calendarClient;
        private readonly \GoogleApiClient $googleApiClient;

        private readonly ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;
        private readonly PhotoService $photoService;

        private readonly GeocodingService $geocodingService;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \ChatClient $chatClient, \CalendarClient $calendarClient,
            \GoogleApiClient $googleApiClient, ConfigurationService $configurationService, CategoryService $categoryService,
            LabelService $labelService, ForecastService $forecastService, PhotoService $photoService, HighlightService $highlightService,
            NoteService $noteService, GeocodingService $geocodingService, \EventPublisher $eventPublisher) {
            $this->placeMapper = new PlaceMapper($databaseProvider, $configurationService, $categoryService, $labelService, $forecastService,
                $photoService, $highlightService, $noteService);
            $this->chatClient = $chatClient;
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->categoryService = $categoryService;
            $this->photoService = $photoService;
            $this->geocodingService = $geocodingService;
            $this->eventPublisher = $eventPublisher;
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

        public function getVisitedCategoriesForInterval(int $start, int $end, ?CategoryCategory $category, VisitedCategoriesSortingStrategy $visitedCategoriesSortingStrategy) : array {
            return $this->placeMapper->selectVisitedCategoriesForInterval($start, $end, $category, $visitedCategoriesSortingStrategy);
        }

        public function getRegularPlace(string $placeId) : ?Place {
            $regularPlaces = $this->doGetRegularPlaces($placeId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, PlaceIncludedEntity::values(), PlaceSortingStrategy::Default);
            return count($regularPlaces) === 1 ? $regularPlaces[0] : NULL;
        }

        public function getRegularPlaces(?string $categoryId, ?string $labelId, ?string $tripId, ?int $year, ?string $albumId, ?string $photoId, ?float $maxQuality, ?int $minStart, ?int $maxEnd, array $includedEntities, PlaceSortingStrategy $placeSortingStrategy) : array {
            return $this->doGetRegularPlaces(NULL, $categoryId, $labelId, $tripId, $year, $albumId, $photoId, $maxQuality, $minStart, $maxEnd, $includedEntities, $placeSortingStrategy);
        }

        public function getCandidatePlace(string $placeId) : ?Place {
            $candidatePlaces = $this->doGetCandidatePlaces($placeId, NULL, NULL, PlaceIncludedEntity::values());
            return count($candidatePlaces) === 1 ? $candidatePlaces[0] : NULL;
        }

        public function getCandidatePlaces(?string $categoryId, ?string $tripId, ?string $labelId, array $includedEntities) : array {
            return $tripId !== NULL
                ? $this->doGetCandidatePlacesForTrip($categoryId, $tripId, $includedEntities)
                : $this->doGetCandidatePlaces(NULL, $categoryId, $labelId, $includedEntities);
        }

        public function getAllPlaceIdentifiers() : array {
            return $this->placeMapper->selectAllPlaceIdentifiers();
        }

        public function getPlaceIdentifierById(string $placeId) : ?PlaceIdentifier {
            return $this->placeMapper->selectPlaceIdentifierById($placeId);
        }

        public function updatePlaceMainHighlight(string $placeId, string $highlightIdentifier) : bool {
            $wasUpdated = $this->placeMapper->updatePlaceMainHighlight($placeId, $highlightIdentifier);

            if ($wasUpdated) {
                $this->eventPublisher->publishPlaceUpdatedEvent($placeId);
            }

            return $wasUpdated;
        }

        public function updatePlaceScore(string $placeId, float $score) : bool {
            return $this->placeMapper->updatePlaceScore($placeId, $score);
        }

        public function updatePlaceQuality(string $placeId, ?float $quality) : bool {
            return $this->placeMapper->updatePlaceQuality($placeId, $quality);
        }

        public function updatePlaceExcerpt(string $placeId, ?string $excerpt) : bool {
            if ($excerpt === NULL) {
                $placeIdentifier = $this->getPlaceIdentifierById($placeId);
                $excerpt = $this->getSuggestedExcerpt($placeIdentifier->getName(), $placeIdentifier->getCountry());
            }
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
                $this->eventPublisher->publishPlaceUpdatedEvent($placeId);
                $this->updatePlaceExcerpt($placeId, $this->getSuggestedExcerpt($name, $place->getCountry()));
            }

            return $wasUpdated;
        }

        public function updatePlaceLocation(string $placeId, float $latitude, float $longitude) : bool {
            $wasUpdated = $this->placeMapper->updatePlaceLocation($placeId, $latitude, $longitude);
            
            if ($wasUpdated) {
                $this->eventPublisher->publishPlaceUpdatedEvent($placeId);
            }

            return $wasUpdated;
        }

        public function movePlaces(string $tripId, int $offset) : array {
            $places = $this->getRegularPlaces(NULL, NULL, $tripId, NULL, NULL, NULL, NULL, NULL, NULL, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);

            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timezoneOffset = $this->getTimezoneOffset($date->getStart(), $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $place->getTimezone());
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
            $places = $this->getRegularPlaces(NULL, NULL, $tripId, NULL, NULL, NULL, NULL, NULL, NULL, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);
            
            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timeOffset = $this->getTimezoneOffset($date->getStart(), $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $place->getTimezone());
                    if ($this->placeMapper->insertPlaceCandidateEvent($place->withUpdatedDates(array(new Date($date->getStart() - $timeOffset - $tripStart, $date->getEnd() - $timeOffset - $tripStart, FALSE, NULL, NULL, NULL, $archivedTripIdentifier))))) {
                        $this->googleApiClient->deleteCalendarEvent(\Calendar::Places->value, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()));
                    }
                }
            }
            
            return $this->doGetCandidatePlacesForTrip(NULL, $archivedTripIdentifier->getId(), array());
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
            $this->placeMapper->deleteAllPlaceEvents();
                
            foreach ($this->calendarClient->getEvents(\Calendar::Places->value) as &$placeEvent) {
                $resolvedLocation = $this->geocodingService->getLocation($placeEvent->getLocation());
                $placeIdentifier = $this->getOrCreatePlaceIdentifier($placeEvent->getSummary(), $resolvedLocation->getCountry(), $placeEvent->getLocation());                        
                $timeOffset = $this->getTimezoneOffset($placeEvent->getStart(), $this->configurationService->getConfigurationEntry("homeLocation")["timezone"], $placeIdentifier->getTimezone());
                $start = $placeEvent->getStart() + $timeOffset;
                $end = $placeEvent->getEnd() + $timeOffset;     
                $isLayover = array_key_exists(self::LAYOVER_ATTRIBUTE_KEY, $placeEvent->getAttributes());
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($start, $end);
                $place = new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                    $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getScore(), $placeIdentifier->getQuality(),
                    $placeIdentifier->getExcerpt(), array(), array(), array(), array(), array(new Date($start, $end, $isLayover, NULL, NULL, NULL, $resolvedTripIdentifier)));

                $this->placeMapper->insertPlaceEvent($place, $placeEvent->getId());

                // Update address to match a common format.
                $newAddress = $this->geocodingService->getAddress($placeIdentifier->getName(), $resolvedLocation);
                if ($this->normalize($placeEvent->getLocation()) !== $this->normalize($newAddress)) {
                    $this->googleApiClient->updateCalendarEventLocation(\Calendar::Places->value, $placeEvent->getId(), $newAddress);
                }
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForCreatedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publishPlaceCreatedEvent($affectedPlaceId);
                $this->eventPublisher->publishPlaceEventCreatedEvent($affectedPlaceId);
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForUpdatedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publishPlaceUpdatedEvent($affectedPlaceId);
                $this->eventPublisher->publishPlaceEventUpdatedEvent($affectedPlaceId);
            }
            
            $affectedPlaceIds = $this->placeMapper->selectPlaceIdsForDeletedPlaceEvents(self::OLD_PLACE_EVENT_TEMPORARY_TABLE);
            foreach ($affectedPlaceIds as &$affectedPlaceId) {
                $this->eventPublisher->publishPlaceRemovedEvent($affectedPlaceId);
                $this->eventPublisher->publishPlaceEventRemovedEvent($affectedPlaceId);
            }

            $this->placeMapper->deleteStalePlaceIdentifiers();
            $this->placeMapper->deleteVisitedCandidatePlaces();
        }

        private function getOrCreatePlaceIdentifier(string $name, string $country, string $address) : PlaceIdentifier {            
            $placeIdentifier = $this->placeMapper->selectPlaceIdentifier($name, $country);
            if ($placeIdentifier !== NULL) {
                return $placeIdentifier;
            }

            if ($country === array_values(array_filter($this->configurationService->getConfigurationEntry("countryNames"), 
                fn($c) => $c["country"] === "UNKNOWN"))[0]["name"]) {
                throw new \InvalidArgumentException("Cannot create an identifier for an unknown country.");
            }
            
            $location = $this->geocodingService->getLocation($address);
            $placeIdentifier = new PlaceIdentifier(NULL, $name, $this->categoryService->getOrCreateCountryCategoryIdentifier($country)->getName(),
                $location->getLatitude(), $location->getLongitude(), $location->getTimezone(), NULL, 0, NULL, $this->getSuggestedExcerpt($name, $country));
            $this->placeMapper->insertPlaceIdentifier($placeIdentifier);
            
            $this->eventPublisher->publishPlaceCreatedEvent($placeIdentifier->getId());

            return $placeIdentifier;
        }

        private function removeSpecialPlace(SpecialPlaceType $specialPlaceType, string $placeId) : bool {
            $wasRemoved = $this->placeMapper->deleteSpecialPlace($specialPlaceType, $placeId);

            if ($wasRemoved) {
                $this->eventPublisher->publishPlaceRemovedEvent($placeId);
                $this->placeMapper->deleteStalePlaceIdentifiers();
            }

            return $wasRemoved;
        }

        private function getSuggestedExcerpt(string $name, string $country) : ?string {
            return $this->chatClient->getResponse(sprintf(self::GET_SUGGESTED_EXCERPT_CHAT_PROMPT_FORMAT, $name, $country));
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
                    $this->placeMapper->deleteSpecialPlace(SpecialPlaceType::Candidate, $place->getId());
                    $this->placeMapper->insertSpecialPlace(SpecialPlaceType::Candidate, $place->getId());
                }
            }
        }
    }
?>