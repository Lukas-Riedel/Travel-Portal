<?php
    namespace Core\Service\Trip;

    use Core\Client\Calendar\Calendar;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
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

    class TripServiceListener {
        
        private const UPDATE_TRIP_STATISTICS_ACTION_NAME = "UPDATE_TRIP_STATISTICS";
        private const UPDATE_TRIP_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;

        private readonly TripService $tripService;

        private readonly PlaceService $placeService;
        private readonly StayService $stayService;
        private readonly FlightService $flightService;

        private readonly ConfigurationService $configurationService;

        private readonly CalendarClient $calendarClient;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, TripService $tripService, PlaceService $placeService, StayService $stayService,
            FlightService $flightService, ConfigurationService $configurationService, CalendarClient $calendarClient,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->stayService = $stayService;
            $this->flightService = $flightService;
            $this->configurationService = $configurationService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->transactionManager = $databaseClient;
        }
        
        public function onCalendarInvalidated(mixed $message) : void {
            // All calendars must be fetched as the entity trip ownership could change when adding/modifying/removing a trip.
            if ($message["calendar"] === Calendar::Trips->value) {
                $this->transactionManager->executeAtomically(function() {
                    $this->tripService->removeAllDayTripsTrips();
                    $this->tripService->refreshCalendar();
                    $this->placeService->refreshCalendar($this->tripService);
                    $this->stayService->refreshCalendar($this->tripService);
                    $this->flightService->refreshCalendar(array_filter(FlightType::cases(), fn($type) => $type->getCalendar() !== null), $this->tripService);
                    $this->tripService->updateAllDayTripsTripsDates();
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

        public function onSchedulerTriggered(mixed $message) : void {   
            if ($this->scheduler->requestExecution(self::UPDATE_TRIP_STATISTICS_ACTION_NAME, self::UPDATE_TRIP_STATISTICS_ACTION_INTERVAL)) {
                $dayTripsTripName = $this->configurationService->getConfigurationEntry("trips")["dayTripsName"];
                $trips = $this->tripService->getRegularTrips(null, null, time(), array(), TripSortingStrategy::OldestAscending);
                
                foreach ($trips as &$trip) {
                    if ($trip->getName() !== $dayTripsTripName) {
                        $this->eventPublisher->publish(Event::TripStatisticsInvalidated($trip->getId()));
                    }
                }                        
            }
        }
    }
?>