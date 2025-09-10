<?php
    namespace Core\Service\Statistics;

    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryService;
    use Core\Service\Flight\Airport;
    use Core\Service\Flight\Flight;
    use Core\Service\Flight\FlightService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class StatisticsServiceListener {
        
        private const UPDATE_OVERALL_STATISTICS_ACTION_NAME = "UPDATE_OVERALL_STATISTICS";
        private const UPDATE_OVERALL_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;

        private readonly StatisticsService $statisticsService;

        private readonly PlaceService $placeService;
        private readonly TripService $tripService;
        private readonly CategoryService $categoryService;
        private readonly FlightService $flightService;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(StatisticsService $statisticsService, PlaceService $placeService,
            TripService $tripService, CategoryService $categoryService, FlightService $flightService,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->statisticsService = $statisticsService;
            $this->placeService = $placeService;
            $this->tripService = $tripService;
            $this->categoryService = $categoryService;
            $this->flightService = $flightService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onCategoryUpdated(mixed $message) : void {
            $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["categoryId"]);
            if ($categoryIdentifier !== null) {
                $this->statisticsService->updateCategoryStatistics($categoryIdentifier);
            }
        }

        public function onCategoryStatisticsInvalidated(mixed $message) : void {
            $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["categoryId"]);
            if ($categoryIdentifier !== null) {
                $this->statisticsService->updateCategoryStatistics($categoryIdentifier);
            }
        }

        public function onExpenseCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onExpenseUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onExpenseRemoved(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFitnessDataUpdated(mixed $message) : void {
            $trips = $this->tripService->getTripsContainingInterval($message["start"], $message["end"]);
            foreach ($trips as &$trip) {
                if (!$this->tripService->isDayTripsTrip($trip)) {
                    $this->statisticsService->updateTripStatistics($trip);
                }
            }
        }

        public function onFlightLogged(mixed $message) : void {
            $flight = new Flight($message["flight"], null, null, null, null,
                new Airport(null, $message["from"], null, null, null, null, null, null),
                new Airport(null, $message["to"], null, null, null, null, null, null),
                $message["scheduledDeparture"], $message["scheduledArrival"], null);
            $tripId = $this->flightService->getTripIdForFlight($flight);
            if ($tripId !== null) {
                $trip = $this->tripService->getRegularTrip($tripId);
                if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                    $this->statisticsService->updateTripStatistics($trip);
                }
            }
        }

        public function onFlightEventCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFlightEventUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFlightEventRemoved(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onPlaceUpdated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === null) {
                return;
            }
            
            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== null && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $trip = $this->tripService->getRegularTrip($tripId);
                if (!$this->tripService->isDayTripsTrip($trip)) {
                    $this->statisticsService->updateTripStatistics($trip);
                }
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }
        }

        public function onPlaceRemoved(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === null) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== null && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $trip = $this->tripService->getRegularTrip($tripId);
                if (!$this->tripService->isDayTripsTrip($trip)) {
                    $this->statisticsService->updateTripStatistics($trip);
                }
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }
        }

        public function onPlaceEventCreated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === null) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== null && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->eventPublisher->publish(Event::TripStatisticsInvalidated($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->eventPublisher->publish(Event::CategoryStatisticsInvalidated($category->getId()));
            }        
        }

        public function onPlaceEventUpdated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === null) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== null && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->eventPublisher->publish(Event::TripStatisticsInvalidated($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->eventPublisher->publish(Event::CategoryStatisticsInvalidated($category->getId()));
            }
        }

        public function onPlaceEventRemoved(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === null) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== null && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $trip = $this->tripService->getRegularTrip($tripId);
                if (!$this->tripService->isDayTripsTrip($trip)) {
                    $this->statisticsService->updateTripStatistics($trip);
                }
            }

            foreach ($place->getCategories() as &$category) {
                $this->eventPublisher->publish(Event::CategoryStatisticsInvalidated($category->getId()));
            }
        }

        public function onStayEventCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onStayEventUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onStayEventRemoved(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripEventCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripEventUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripEventRemoved(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onYearStatisticsUpdated(mixed $message) : void {
            $this->statisticsService->updateOverallStatistics();
        }

        public function onCategoryStatisticsUpdated(mixed $message) : void {
            $this->statisticsService->updateOverallStatistics();
        }

        public function onTripStatisticsUpdated(mixed $message) : void {
            $this->statisticsService->updateYearStatistics($message["year"]);
        }

        public function onOverallStatisticsInvalidated(mixed $message) : void {
            $this->statisticsService->updateOverallStatistics();
        }

        public function onYearStatisticsInvalidated(mixed $message) : void {
            $this->statisticsService->updateYearStatistics($message["year"]);
        }

        public function onTripStatisticsInvalidated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== null && !$this->tripService->isDayTripsTrip($trip)) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_OVERALL_STATISTICS_ACTION_NAME, self::UPDATE_OVERALL_STATISTICS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::OverallStatisticsInvalidated());
            }
        }
    }
?>