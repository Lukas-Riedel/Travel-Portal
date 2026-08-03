<?php
    namespace Core\Service\Flight;

    use Common\Client\Cache\CacheClient;
    use Common\Service\Authentication\UserRole;
    use Core\Common\CommonConstants;
    use Monolog\Logger;
    use Core\Service\Trip\TripService;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Client\Calendar\CalendarClient;
    use Core\Service\Device\DeviceService;
    use Core\Service\Device\DeviceType;

    class FlightServiceListener {
        
        private const LOG_FLIGHTS_ACTION_NAME = "LOG_FLIGHTS";
        private const LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL = 4 * CommonConstants::ONE_HOUR_SECONDS;
                
        private const FLIGHT_REMINDER_CACHE_KEY_FORMAT = "FlightServiceListener:FlightReminder:%s:%s:%s";

        private const HI_TIME_FORMAT = "H:i";
        private const KEY_PLACEHOLDER_FORMAT = "{%s}";
        private const ESTIMATED_ARRIVAL_TIME_MARGIN_SECONDS = 5 * 60;

        private readonly FlightService $flightService;
        private readonly DeviceService $deviceService;
        private readonly TripService $tripService;
        private readonly ConfigurationService $configurationService;
        private readonly CalendarClient $calendarClient;
        private readonly CacheClient $distributedCacheClient;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly Logger $logger;

        public function __construct(FlightService $flightService, DeviceService $deviceService, TripService $tripService, ConfigurationService $configurationService, CalendarClient $calendarClient,
            CacheClient $distributedCacheClient, EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger) {
            $this->flightService = $flightService;
            $this->deviceService = $deviceService;
            $this->tripService = $tripService;
            $this->configurationService = $configurationService;
            $this->calendarClient = $calendarClient;
            $this->distributedCacheClient = $distributedCacheClient;
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

            $relevantDevices = $this->deviceService->getDevices(DeviceType::BridgeX, UserRole::FlightEdit);

            $intervalSelector = function($lastTriggered) use(&$firstNonLoggedFlight, &$relevantDevices) {
                $scheduledArrival = $firstNonLoggedFlight->getEnd();
                $expectedArrival = $scheduledArrival + $this->flightService->getAverageFlightDelay();

                $flightMidpoint = ($firstNonLoggedFlight->getStart() + $scheduledArrival) / 2;
                $attemptAlreadyMadeInSecondHalf = $lastTriggered > $flightMidpoint;
                $hasNewDeviceActivityInSecondHalf = false;

                if (!$attemptAlreadyMadeInSecondHalf) {
                    foreach ($relevantDevices as &$device) {
                        if ($device->getLastSeen() > $flightMidpoint) {
                            $hasNewDeviceActivityInSecondHalf = true;
                            break;
                        }
                    }
                }

                // Attempt to log the flight if the device activity occurred after the flight midpoint.
                if ($hasNewDeviceActivityInSecondHalf) {
                    return 0;
                }

                // If there was already an attempt to log the flight, we have the estimated arrival time -> use it for the next trigger.
                $estimatedArrival = $this->flightService->getEstimatedArrivalTime($firstNonLoggedFlight->getFlight());
                if ($estimatedArrival !== null && $estimatedArrival + self::ESTIMATED_ARRIVAL_TIME_MARGIN_SECONDS > $lastTriggered) {
                    return $estimatedArrival + self::ESTIMATED_ARRIVAL_TIME_MARGIN_SECONDS - $lastTriggered;
                }

                // If we don't have the estimated arrival time, we can use the expected arrival time of the flight to determine when to trigger the next attempt.
                if ($expectedArrival > $lastTriggered) {
                    return $expectedArrival - $lastTriggered;
                }

                // It's already past the expected arrival time, and the estimated arrival time of the flight is unknown.
                return self::LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL;
            };

            if ($this->scheduler->requestDynamicExecution(self::LOG_FLIGHTS_ACTION_NAME, $intervalSelector)) {
                $this->eventPublisher->publish(Event::FlightArrived($firstNonLoggedFlight->getFlight(), $firstNonLoggedFlight->getFrom()->getShortName(),
                    $firstNonLoggedFlight->getTo()->getShortName(), $firstNonLoggedFlight->getStart()));
            }

            foreach ($this->flightService->getAllNonLoggedFlights() as &$flight) {
                if ($flight->getStart() < time()) {
                    continue;
                }

                foreach ($this->configurationService->getConfigurationEntry("flightReminders") as &$flightReminder) {
                    if (time() + $flightReminder["secondsBefore"] < $flight->getStart()) {
                        continue;
                    }

                    $cacheKey = $this->getFlightReminderCacheKey($flight->getFlight(), $flight->getStart(), $flightReminder["title"]);
                    if ($this->distributedCacheClient->trySet($cacheKey, true, $flightReminder["secondsBefore"])) {
                        $this->eventPublisher->publish(Event::FlightReminderReceived($flight->getFlight(), $flightReminder["title"], $this->createText($flightReminder["text"], array("flight" => $flight->getFlight(),
                            "formattedTime" => (new \DateTime())->setTimestamp($flight->getStart())->setTimezone(new \DateTimeZone($flight->getFrom()->getTimezone()))->format(self::HI_TIME_FORMAT)))));
                    }
                }
            }
        }

        private function getFlightReminderCacheKey(string $flight, int $scheduledDeparture, string $reminderName) : string {
            return sprintf(self::FLIGHT_REMINDER_CACHE_KEY_FORMAT, $flight, $scheduledDeparture, $reminderName);
        }
        
        private function createText(string $format, array $context) : string {
            return str_replace(array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)), array_values($context), $format);
        }
    }
?>