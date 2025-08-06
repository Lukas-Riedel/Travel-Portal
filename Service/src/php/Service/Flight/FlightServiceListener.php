<?php
    namespace Service\Service\Flight;

    use Service\Service\Trip\TripService;

    class FlightServiceListener {
        
        private const LOG_FLIGHTS_ACTION_NAME = "LOG_FLIGHTS";
        private const LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL = 14400;

        private readonly FlightService $flightService;
        
        private readonly TripService $tripService;

        private readonly \CalendarClient $calendarClient;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(FlightService $flightService, TripService $tripService, \CalendarClient $calendarClient,
            \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->flightService = $flightService;
            $this->tripService = $tripService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onCalendarInvalidated(mixed $message) : void {
            foreach (FlightType::cases() as &$flightType) {
                if ($flightType->getCalendar()?->value === $message["calendar"]) {
                    $this->flightService->refreshCalendar(array($flightType), $this->tripService);                    
                    $this->tripService->updateAllDayTripsTripsDates();
                }
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            foreach (FlightType::cases() as &$flightType) {
                if ($flightType->getCalendar()?->value === $message["calendar"]) {
                    $this->calendarClient->watchCalendar($flightType->getCalendar()->value);
                }
            }
        }

        public function onFlightArrived(mixed $message) : void {            
            $this->flightService->fetchAndLogFlight($message["flight"], $message["from"], $message["to"], $message["scheduledDeparture"]);
        }

        public function onSchedulerTriggered(mixed $message) : void {
            foreach ($message["actions"] as &$action) {
                if ($action["name"] === self::LOG_FLIGHTS_ACTION_NAME) {
                    $firstNonLoggedFlight = $this->flightService->getFirstNonLoggedFlight();
                    if ($firstNonLoggedFlight === NULL) {
                        return;
                    }

                    $loggingInterval = $firstNonLoggedFlight->getEnd() < $action["lastTriggered"]
                        ? self::LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL // The flight was already tried to be logged but unsuccessfully. Try again with some delay.
                        : time() - $action["lastTriggered"] + $firstNonLoggedFlight->getEnd() + $this->flightService->getAverageFlightDelay() - time();

                    if (time() - $action["lastTriggered"] > $loggingInterval) {
                        $this->eventPublisher->publishFlightArrivedEvent($firstNonLoggedFlight->getFlight(), $firstNonLoggedFlight->getFrom()->getName(),
                            $firstNonLoggedFlight->getTo()->getName(), $firstNonLoggedFlight->getStart());
                        $this->scheduler->recordEventsTriggered(self::LOG_FLIGHTS_ACTION_NAME);
                    }
                }
            }
        }
    }
?>