<?php
    namespace Service\Service\Trip;

    use Service\Service\Flight\FlightService;
    use Service\Service\Flight\FlightType;
    use Service\Service\Highlight\HighlightType;
    use Service\Service\Place\PlaceService;
    use Service\Service\Stay\StayService;

    class TripServiceListener {
        
        private const UPDATE_TRIP_STATISTICS_ACTION_NAME = "UPDATE_TRIP_STATISTICS";
        private const UPDATE_TRIP_STATISTICS_ACTION_INTERVAL = 86400 * 14;

        private readonly TripService $tripService;

        private readonly PlaceService $placeService;
        private readonly StayService $stayService;
        private readonly FlightService $flightService;

        private readonly \ConfigurationService $configurationService;

        private readonly \CalendarClient $calendarClient;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(TripService $tripService, PlaceService $placeService, StayService $stayService,
            FlightService $flightService, \ConfigurationService $configurationService, \CalendarClient $calendarClient,
            \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->stayService = $stayService;
            $this->flightService = $flightService;
            $this->configurationService = $configurationService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }
        
        public function onCalendarInvalidated(mixed $message) : void {
            // All calendars must be fetched as the entity trip ownership could change when adding/modifying/removing a trip.
            if ($message["calendar"] === \Calendar::Trips->value) {
                $this->tripService->deleteAllDayTripsTrips();
                $this->tripService->refreshCalendar();
                $this->placeService->refreshCalendar($this->tripService);
                $this->stayService->refreshCalendar($this->tripService);
                $this->flightService->refreshCalendar(FlightType::cases(), $this->tripService);
                $this->tripService->updateAllDayTripsTripsDates();
            }
        }
        
        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === \Calendar::Trips->value) {
                $this->calendarClient->watchCalendar(\Calendar::Trips->value);
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Trip->name) {
                $tripIdentifier = $this->tripService->getTripIdentifierById($message["entityId"]);
                if ($tripIdentifier !== NULL && $tripIdentifier->getMainHighlight() === NULL) {
                    $this->tripService->updateTripMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {            
            if ($message["action"] === self::UPDATE_TRIP_STATISTICS_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::UPDATE_TRIP_STATISTICS_ACTION_INTERVAL) {
                $dayTripsTripName = $this->configurationService->getConfigurationForTypeAndKey("specialTripNames", "dayTrips");
                $trips = $this->tripService->getRegularTrips(NULL, NULL, time(), array(), TripSortingStrategy::StartAscending);
                foreach ($trips as &$trip) {
                    if ($trip->getName() !== $dayTripsTripName) {
                        $this->eventPublisher->publishTripStatisticsInvalidatedEvent($trip->getId());
                    }
                }                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_TRIP_STATISTICS_ACTION_NAME);
            }
        }
    }
?>