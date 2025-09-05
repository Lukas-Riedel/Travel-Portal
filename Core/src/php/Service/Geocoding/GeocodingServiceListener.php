<?php
    namespace Core\Service\Geocoding;

    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class GeocodingServiceListener {

        private const TRACK_LOCATION_ACTION_NAME = "TRACK_LOCATION";
        private const TRACK_LOCATION_ACTION_INTERVAL = 15 * 60;

        private readonly TripService $tripService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(TripService $tripService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->tripService = $tripService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::TRACK_LOCATION_ACTION_NAME, self::TRACK_LOCATION_ACTION_INTERVAL)) {
                $trips = $this->tripService->getRegularTrips(null, null, null, array(), TripSortingStrategy::OldestDescending);
                foreach ($trips as $trip) {
                    if ($trip->isCurrent() && !$this->tripService->isDayTripsTrip($trip)) {
                        $this->eventPublisher->publishLocationUpdateDetectedEvent();
                        break;
                    }
                }
            }
        }
    }

?>