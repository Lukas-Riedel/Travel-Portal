<?php
    namespace Core\Service\Trip;

    use Core\Client\Calendar\Calendar;
    use Core\Common\CommonConstants;
    use Core\Service\Flight\FlightService;
    use Core\Service\Flight\FlightType;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Place\PlaceService;
    use Core\Service\Stay\StayService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Calendar\CalendarClient;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Photo\PhotoService;

    class TripServiceListener {
        
        private const UPDATE_TRIP_STATISTICS_ACTION_NAME = "UPDATE_TRIP_STATISTICS";
        private const UPDATE_TRIP_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;

        private readonly TripService $tripService;
        private readonly PlaceService $placeService;
        private readonly StayService $stayService;
        private readonly FlightService $flightService;
        private readonly PhotoService $photoService;
        private readonly HighlightService $highlightService;
        private readonly CalendarClient $calendarClient;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly TransactionManager $transactionManager;

        private readonly int $maxHighlightsPerTripCount;

        public function __construct(DatabaseClient $databaseClient, TripService $tripService, PlaceService $placeService, StayService $stayService,
            FlightService $flightService, PhotoService $photoService, HighlightService $highlightService, CalendarClient $calendarClient,
            EventPublisher $eventPublisher, Scheduler $scheduler, int $maxHighlightsPerTripCount) {
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->stayService = $stayService;
            $this->flightService = $flightService;
            $this->photoService = $photoService;
            $this->highlightService = $highlightService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->transactionManager = $databaseClient;
            $this->maxHighlightsPerTripCount = $maxHighlightsPerTripCount;
        }
        
        public function onCalendarInvalidated(mixed $message) : void {
            // All calendars must be fetched as the entity trip ownership could change when adding/modifying/removing a trip.
            if ($message["calendar"] === Calendar::Trips->value) {
                $this->transactionManager->executeAtomically(function() {
                    $this->tripService->refreshCalendar();
                    $this->placeService->refreshCalendar($this->tripService);
                    $this->stayService->refreshCalendar($this->tripService);
                    $this->flightService->refreshCalendar(array_filter(FlightType::cases(), fn($type) => $type->getCalendar() !== null), $this->tripService);
                });
            }
        }
        
        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Trips->value) {
                $this->calendarClient->watchCalendar(Calendar::Trips);
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Trip->value) {
                $tripIdentifier = $this->tripService->getTripIdentifierById($message["entityId"]);
                if ($tripIdentifier !== null && $tripIdentifier->getMainHighlight() === null) {
                    $this->tripService->updateTripMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }        

        public function onHighlightRemoved(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Trip->value) {
                $trip = $this->tripService->getRegularTrip($message["entityId"]);
                if ($trip != null && $trip->getMainHighlight() === null && count($trip->getHighlights()) > 0) {
                    $this->tripService->updateTripMainHighlight($trip->getId(), $trip->getHighlights()[0]->getId());
                }
                
                // TODO: At this point, the highlight is already removed and we don't know if it was also in the year or not.
                $this->eventPublisher->publish(Event::HighlightRemoved(HighlightType::Year->value, $trip->getYear(), $message["highlightId"]));
            }
        }
        
        public function onAlbumUpdated(mixed $message) : void {
            $album = $this->photoService->getAlbum($message["albumId"]);
            if ($album !== null) {
                $place = $this->placeService->getRegularPlaceForAlbum($message["albumId"]);
                $photos = $this->photoService->getPhotosForAlbum($album->getId(), $place?->getLatitude(), $place?->getLongitude(), true);

                if ($place !== null && count($photos) > 0) {
                    if ($place->getMainHighlight() === null) {
                        // TODO: Why is this here? Should ine in PlaceServiceListener.
                        $this->highlightService->createPlaceHighlight($place->getId(), $album->getMainPhoto()?->getId() ?? $photos[0]->getId());
                    }

                    $tripIdsWithoutHighlights = array_unique(array_map(fn($trip) => $trip->getId(),
                        array_filter(array_map(fn($date) => $date->getTrip(), $place->getDates()),
                        fn($trip) => $trip !== null && $trip->getMainHighlight() === null)));
                    foreach ($tripIdsWithoutHighlights as &$tripId) {
                        $this->highlightService->createTripHighlight($tripId, $album->getMainPhoto()?->getId() ?? $photos[0]->getId());
                    }
                    
                    $activeTripIds = array_unique(array_map(fn($trip) => $trip->getId(),
                        array_filter(array_map(fn($date) => $date->getTrip(), $place->getDates()),
                        fn($trip) => $trip !== null && ($this->tripService->getRegularTrip($trip->getId())?->isCurrent() ?? false))));
                    foreach ($activeTripIds as &$tripId) {
                        $trip = $this->tripService->getRegularTrip($tripId);
                        if (count($trip->getHighlights()) < $this->maxHighlightsPerTripCount) {
                            $this->tripService->refreshTripHighlights($tripId, $this->maxHighlightsPerTripCount, true);
                        }
                    }
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {   
            if ($this->scheduler->requestExecution(self::UPDATE_TRIP_STATISTICS_ACTION_NAME, self::UPDATE_TRIP_STATISTICS_ACTION_INTERVAL)) {
                $trips = $this->tripService->getRegularTrips(null, null, time(), array(), TripSortingStrategy::OldestAscending);
                
                foreach ($trips as &$trip) {
                    $this->eventPublisher->publish(Event::TripStatisticsInvalidated($trip->getId()));
                }                        
            }
        }
    }
?>