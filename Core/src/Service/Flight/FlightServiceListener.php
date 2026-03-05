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
        private const ESTIMATED_ARRIVAL_TIME_MARGIN_SECONDS = 5 * 60;

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

            $intervalSelector = function($lastTriggered) use(&$firstNonLoggedFlight) {
                if ($firstNonLoggedFlight->getEnd() + $this->flightService->getAverageFlightDelay() > $lastTriggered) {
                    return $firstNonLoggedFlight->getEnd() + $this->flightService->getAverageFlightDelay() - $lastTriggered;
                }

                // There was already an attempt to log the flight, but the flight has not landed yet.
                $estimatedArrival = $this->flightService->getEstimatedArrivalTime($firstNonLoggedFlight->getFlight());
                if ($estimatedArrival !== null && $estimatedArrival + self::ESTIMATED_ARRIVAL_TIME_MARGIN_SECONDS > $lastTriggered) {
                    return $estimatedArrival + self::ESTIMATED_ARRIVAL_TIME_MARGIN_SECONDS - $lastTriggered;
                }

                // Estimated arrival time of the flight is unknown.
                return self::LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL;
            };

            if ($this->scheduler->requestDynamicExecution(self::LOG_FLIGHTS_ACTION_NAME, $intervalSelector)) {
                $this->eventPublisher->publish(Event::FlightArrived($firstNonLoggedFlight->getFlight(), $firstNonLoggedFlight->getFrom()->getShortName(),
                    $firstNonLoggedFlight->getTo()->getShortName(), $firstNonLoggedFlight->getStart()));
            }
        }
    }
?>