<?php
    require_once(dirname(__FILE__) . "/PlaceMapper.php");
    require_once(dirname(__FILE__) . "/../Model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/../Model/Place.php");
    require_once(dirname(__FILE__) . "/../Model/Date.php");

    class PlaceService {
        
        private const OLD_PLACE_EVENT_TEMPORARY_TABLE = "old_place_event";
        private const UTC_DATE_TIME_FORMAT = "m/d/Y H:i:s";
        private const LAYOVER_ATTRIBUTE_KEY = "Layover";
        
        private readonly PlaceMapper $placeMapper;

        private readonly ChatClient $chatClient;
        private readonly CalendarClient $calendarClient;
        private readonly GoogleApiClient $googleApiClient;

        private readonly ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;
        private readonly PhotoService $photoService;

        private readonly GeocodingService $geocodingService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, ChatClient $chatClient, CalendarClient $calendarClient, GoogleApiClient $googleApiClient, ConfigurationService $configurationService, 
            CategoryService $categoryService, LabelService $labelService, ForecastService $forecastService, PhotoService $photoService, HighlightService $highlightService, GeocodingService $geocodingService,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->placeMapper = new PlaceMapper($databaseProvider, $categoryService, $labelService, $forecastService, $photoService, $highlightService);
            $this->chatClient = $chatClient;
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->categoryService = $categoryService;
            $this->photoService = $photoService;
            $this->geocodingService = $geocodingService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
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

        public function getLayoversForTrip(string $tripId) : array {
            return $this->placeMapper->selectLayoversForTrip($tripId);
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

        public function getPlaceIdentifier(string $name, string $country) : ?PlaceIdentifier {
            return $this->placeMapper->selectPlaceIdentifier($name, $country);
        }

        public function getOrCreatePlaceIdentifier(string $name, string $country, string $address) : PlaceIdentifier {            
            $placeIdentifier = $this->getPlaceIdentifier($name, $country);
            if ($placeIdentifier !== NULL) {
                return $placeIdentifier;
            }

            if ($country === $this->configurationService->getConfigurationForTypeAndKey("countryNames", "UNKNOWN")) {
                throw new InvalidArgumentException("Cannot create an identifier for an unknown country.");
            }
            
            $location = $this->geocodingService->getLocation($address);
            $placeIdentifier = new PlaceIdentifier(NULL, $name, $this->categoryService->getOrCreateCountryCategoryIdentifier($country)->getId(), $location->getTimezone(),
                $location->getLatitude(), $location->getLongitude(), NULL, $this->getSuggestedExcerpt($name, $country));
            $this->placeMapper->insertPlaceIdentifier($placeIdentifier);

            return $placeIdentifier;
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
                        $wasUpdated &= $this->googleApiClient->updateCalendarEventSummary(Calendar::Places->value, $eventId, $name);
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
                    $this->googleApiClient->updateCalendarEventDates(Calendar::Places->value, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()), $date->getStart() - $timezoneOffset + $offset, $date->getEnd() - $timezoneOffset + $offset);
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
                    $allCalendarEventsCreated &= $this->googleApiClient->createCalendarEvent(Calendar::Places->value, $place->getName(), $address, $startOffset + $date->getStart(), $startOffset + $date->getEnd());
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
                        $this->googleApiClient->deleteCalendarEvent(Calendar::Places->value, $this->placeMapper->selectPlaceEventId($place->getId(), $date->getStart()));
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
                
            foreach ($this->calendarClient->getEvents(Calendar::Places->value) as &$placeEvent) {
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
                    $this->googleApiClient->updateCalendarEventLocation(Calendar::Places->value, $placeEvent->getId(), $newAddress);
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
            $timezone = new DateTimeZone($fromTimezone);
            $dateTimeHome = new DateTime(date(self::UTC_DATE_TIME_FORMAT, $timestamp), new DateTimeZone($toTimezone));
            return $timezone->getOffset($dateTimeHome) - (new DateTimeZone($toTimezone))->getOffset($dateTimeHome);
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
        
        public function onAlbumUpdated(mixed $message) : void {
            global $eventPublisher;
            
            $places = $this->getRegularPlaces(NULL, NULL, NULL, NULL, $message["albumId"], NULL, NULL, array(PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Categories->value));
            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $trip = $date->getTrip();
                    if ($trip !== NULL) {
                        $eventPublisher->publishTripStatisticsInvalidatedEvent($trip->getId());
                    }
                }

                foreach ($place->getCategories() as &$category) {
                    $eventPublisher->publishCategoryUpdatedEvent($category->getId());
                }
            }
        }

        public function onCalendarInvalidated(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            if ($message["calendar"] === Calendar::Places->value) {
                $this->refreshCalendar($tripService);
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            global $calendarClient;

            if ($message["calendar"] === Calendar::Places->value) {
                $calendarClient->watchCalendar($message["calendar"]);
            }
        }

        public function onCategoryInvalidated(mixed $message) : void {
            $places = $this->getRegularPlaces($message["categoryId"], NULL, NULL, NULL, NULL, NULL, NULL, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publishPlaceUpdatedEvent($place->getPlaceIdentifier());
            }
            
            $places = $this->getCandidatePlaces($message["categoryId"], NULL, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publishPlaceUpdatedEvent($place->getPlaceIdentifier());
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->name) {
                $placeIdentifier = $this->getPlaceIdentifierById($message["entityId"]);
                if ($placeIdentifier !== NULL && $placeIdentifier->getMainHighlight() === NULL) {
                    $this->updatePlaceMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }
    }

    enum SpecialPlaceType {
        case Candidate;
        case Permanent;

        public function getTableName() : string {
            return match ($this) {
                self::Candidate => "place_candidate",
                self::Permanent => "place_permanent"
            };
        }
    }

    enum PlaceIncludedEntity : string {
        case Excerpt = "EXCERPT";
        case Categories = "CATEGORIES";
        case Highlights = "HIGHLIGHTS";
        case Labels = "LABELS";
        case Dates = "DATES";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>