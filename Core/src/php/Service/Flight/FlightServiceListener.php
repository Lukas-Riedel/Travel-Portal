<?php
    namespace Core\Service\Flight;

    use Core\Common\CommonConstants;
    use Monolog\Logger;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Client\Calendar\CalendarClient;

    class FlightServiceListener {
        
        private const LOG_FLIGHTS_ACTION_NAME = "LOG_FLIGHTS";
        private const LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL = 4 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly FlightService $flightService;
        
        private readonly TripService $tripService;

        private readonly CalendarClient $calendarClient;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private readonly Logger $logger;

        public function __construct(FlightService $flightService, TripService $tripService, CalendarClient $calendarClient,
            EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger) {
            $this->flightService = $flightService;
            $this->tripService = $tripService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
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
                    $this->calendarClient->watchCalendar($flightType->getCalendar());
                }
            }
        }

        public function onFlightArrived(mixed $message) : void {          
            foreach ($this->flightService->getAllNonLoggedFlights() as &$flight) {
                if ($flight->getFlight() === $message["flight"] && $flight->getFrom()->getShortName() === $message["from"]
                    && $flight->getTo()->getShortName() === $message["to"] && $flight->getStart() === $message["scheduledDeparture"]) {
                        $this->flightService->fetchAndLogFlight($message["flight"], $message["from"], $message["to"], $message["scheduledDeparture"]);
                        return;
                }
            }
            
            $this->logger->warning("The flight '" . $flight->getFlight() . "' is already logged.", $message);
        }

        public function onSchedulerTriggered(mixed $message) : void {
            $firstNonLoggedFlight = $this->flightService->getFirstNonLoggedFlight();
            if ($firstNonLoggedFlight === null) {
                return;
            }

            $intervalSelector = fn($lastTriggered) => $firstNonLoggedFlight->getEnd() < $lastTriggered
                ? self::LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL // The flight was already tried to be logged but unsuccessfully. Try again with some delay.
                : time() - $lastTriggered + $firstNonLoggedFlight->getEnd() + $this->flightService->getAverageFlightDelay() - time();

            if ($this->scheduler->requestDynamicExecution(self::LOG_FLIGHTS_ACTION_NAME, $intervalSelector)) {
                $this->eventPublisher->publish(Event::FlightArrived($firstNonLoggedFlight->getFlight(), $firstNonLoggedFlight->getFrom()->getShortName(),
                    $firstNonLoggedFlight->getTo()->getShortName(), $firstNonLoggedFlight->getStart()));
            }
        }
    }
?>