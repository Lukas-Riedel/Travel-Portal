<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;
    use Monolog\Logger;

    class FitnessServiceListener {

        private const FETCH_FITNESS_ACTION_NAME = "FETCH_FITNESS";
        private const FETCH_FITNESS_ACTION_INTERVAL = CommonConstants::FITNESS_RECORD_DURATION_SECONDS;

        // Only around 100 intervals fit into the size limit of 4kB for an FCM message.
        private const INTERVALS_LIMIT = 100;

        private readonly FitnessService $fitnessService;

        private readonly TripService $tripService;
        private readonly PlaceService $placeService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private readonly Logger $logger;

        public function __construct(FitnessService $fitnessService, TripService $tripService, PlaceService $placeService,
            EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger) {
            $this->fitnessService = $fitnessService;
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
        }

        public function onPlaceEventRemoved(mixed $message) : void {
            $this->fitnessService->removeStaleFitnessRecords();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_FITNESS_ACTION_NAME, self::FETCH_FITNESS_ACTION_INTERVAL)) {
                $timestampsToUpdate = array();

                $validTimestamps = array_flip($this->fitnessService->getValidFitnessRecordTimestamps());
                $trips = $this->tripService->getRegularTrips(null, time(), null, array(), TripSortingStrategy::OldestAscending);

                foreach ($trips as &$trip) {
                    if ($this->tripService->isDayTripsTrip($trip)) {
                        $places = $this->placeService->getRegularPlaces(null, null, $trip->getId(), null, null, null, null, null, null,
                            array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                        foreach ($places as &$place) {
                            foreach ($place->getDates() as &$date) {
                                $currentTimestamp = $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS);
                                while ($currentTimestamp + CommonConstants::FITNESS_RECORD_DURATION_SECONDS <= min(time(), $date->getEnd() - ($date->getEnd() % CommonConstants::ONE_DAY_SECONDS) + CommonConstants::ONE_DAY_SECONDS)) {
                                    if (!isset($validTimestamps[$currentTimestamp])) {
                                        $timestampsToUpdate[] = $currentTimestamp;
                                    }
                                    $currentTimestamp += CommonConstants::FITNESS_RECORD_DURATION_SECONDS;
                                }

                                if (!isset($validTimestamps[$currentTimestamp])) {
                                    $timestampsToUpdate[] = $date->getStart();
                                }
                            }
                        }
                    }
                    else {
                        $currentTimestamp = $trip->getStart();
                        while ($currentTimestamp + CommonConstants::FITNESS_RECORD_DURATION_SECONDS <= min(time(), $trip->getEnd())) {
                            if (!in_array($currentTimestamp, $validTimestamps)) {
                                $timestampsToUpdate[] = $currentTimestamp;
                            }
                            $currentTimestamp += CommonConstants::FITNESS_RECORD_DURATION_SECONDS;
                        }
                    }
                }

                $timestampsToUpdate = array_unique($timestampsToUpdate);
                if (count($timestampsToUpdate) > 0) {                
                    $intervals = array();
                    foreach ($timestampsToUpdate as &$timestampToUpdate) {
                        $intervals[] = array(
                            "start" => $timestampToUpdate,
                            "end" => $timestampToUpdate + CommonConstants::FITNESS_RECORD_DURATION_SECONDS
                        );

                        if (count($intervals) >= self::INTERVALS_LIMIT) {
                            // Other intervals will eventually be fetched in the next scheduler trigger.
                            $this->logger->warning("There are " . count($timestampsToUpdate) . " records to update but only " . self::INTERVALS_LIMIT . "  can be updated at once.", 
                                array("timestamps" => $timestampsToUpdate));
                            break;
                        }
                    }
                    
                    $this->logger->debug("Totally " . count($intervals) . " records will be updated.", array("intervals" => $intervals));
                    $this->eventPublisher->publish(Event::FitnessActivityDetected($intervals));
                }
            }
        }
    }
?>