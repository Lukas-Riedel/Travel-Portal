<?php
    namespace Service\Service\Statistics;

    use Service\Service\Category\CategoryService;
use Service\Service\Flight\Airport;
use Service\Service\Flight\Flight;
use Service\Service\Flight\FlightService;
use Service\Service\Place\PlaceService;
    use Service\Service\Trip\TripService;

    class StatisticsServiceListener {
        
        private const UPDATE_OVERALL_STATISTICS_ACTION_NAME = "UPDATE_OVERALL_STATISTICS";
        private const UPDATE_OVERALL_STATISTICS_ACTION_INTERVAL = 86400 * 14;

        private readonly StatisticsService $statisticsService;

        private readonly PlaceService $placeService;
        private readonly TripService $tripService;
        private readonly CategoryService $categoryService;
        private readonly FlightService $flightService;
        
        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(StatisticsService $statisticsService, PlaceService $placeService,
            TripService $tripService, CategoryService $categoryService, FlightService $flightService,
            \EventPublisher $eventPublisher, \Scheduler $scheduler) {
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
            if ($categoryIdentifier !== NULL) {
                $this->statisticsService->updateCategoryStatistics($categoryIdentifier);
            }
        }

        public function onCategoryStatisticsInvalidated(mixed $message) : void {
            $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["categoryId"]);
            if ($categoryIdentifier !== NULL) {
                $this->statisticsService->updateCategoryStatistics($categoryIdentifier);
            }
        }

        public function onExpenseCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onExpenseUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onExpenseRemoved(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFitnessDataUpdated(mixed $message) : void {
            $trips = $this->tripService->getTripsContainingInterval($message["start"], $message["end"]);
            foreach ($trips as &$trip) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFlightLogged(mixed $message) : void {
            $flight = new Flight($message["flight"], NULL, NULL, NULL, NULL,
                new Airport(NULL, $message["from"]["name"], NULL, NULL, NULL, NULL, NULL),
                new Airport(NULL, $message["to"]["name"], NULL, NULL, NULL, NULL, NULL),
                $message["start"], $message["end"], NULL);
            $tripId = $this->flightService->getTripIdForFlight($flight);
            if ($tripId !== NULL) {
                $trip = $this->tripService->getRegularTrip($tripId);
                if ($trip !== NULL) {
                    $this->statisticsService->updateTripStatistics($trip);
                }
            }
        }

        public function onFlightEventCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFlightEventUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onFlightEventDeleted(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onPlaceUpdated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === NULL) {
                return;
            }
            
            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== NULL && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->statisticsService->updateTripStatistics($this->tripService->getRegularTrip($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }
        }

        public function onPlaceDeleted(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === NULL) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== NULL && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->statisticsService->updateTripStatistics($this->tripService->getRegularTrip($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }
        }

        public function onPlaceEventCreated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === NULL) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== NULL && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->statisticsService->updateTripStatistics($this->tripService->getRegularTrip($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }        
        }

        public function onPlaceEventUpdated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === NULL) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== NULL && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->statisticsService->updateTripStatistics($this->tripService->getRegularTrip($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }
        }

        public function onPlaceEventDeleted(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === NULL) {
                return;
            }

            $tripIdsToUpdate = array();
            foreach ($place->getDates() as &$date) {
                if ($date->getTrip() !== NULL && !in_array($date->getTrip()->getId(), $tripIdsToUpdate)) {
                    $tripIdsToUpdate[] = $date->getTrip()->getId();
                }
            }

            foreach ($tripIdsToUpdate as &$tripId) {
                $this->statisticsService->updateTripStatistics($this->tripService->getRegularTrip($tripId));
            }

            foreach ($place->getCategories() as &$category) {
                $this->statisticsService->updateCategoryStatistics($category);
            }
        }

        public function onStayEventCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onStayEventUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onStayEventDeleted(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripEventCreated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripEventUpdated(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onTripEventDeleted(mixed $message) : void {
            $trip = $this->tripService->getRegularTrip($message["tripId"]);
            if ($trip !== NULL) {
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
            if ($trip !== NULL) {
                $this->statisticsService->updateTripStatistics($trip);
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::UPDATE_OVERALL_STATISTICS_ACTION_NAME
                && time() - $message["lastTriggered"] > self::UPDATE_OVERALL_STATISTICS_ACTION_INTERVAL) {
                $this->eventPublisher->publishOverallStatisticsInvalidatedEvent();                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_OVERALL_STATISTICS_ACTION_NAME);
            }
        }
    }
?>