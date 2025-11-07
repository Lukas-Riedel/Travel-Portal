<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Place\PlaceService;
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

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private readonly Logger $logger;

        public function __construct(FitnessService $fitnessService, TripService $tripService, EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger) {
            $this->fitnessService = $fitnessService;
            $this->tripService = $tripService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
        }

        public function onPlaceEventRemoved(mixed $message) : void {
            $this->fitnessService->removeUnreferencedFitnessRecords($this->getAllRequiredFitnessRecordTimestamps());
        }

        public function onTripEventRemoved(mixed $message) : void {
            $this->fitnessService->removeUnreferencedFitnessRecords($this->getAllRequiredFitnessRecordTimestamps());
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_FITNESS_ACTION_NAME, self::FETCH_FITNESS_ACTION_INTERVAL)) {
                $validTimestamps = array_flip($this->fitnessService->getAllValidFitnessRecordTimestamps());
                $allRequiredTimestamps = $this->getAllRequiredFitnessRecordTimestamps();
                
                $timestampsToUpdate = array();
                foreach ($allRequiredTimestamps as &$requiredTimestamp) {
                    if (!isset($validTimestamps[$requiredTimestamp])) {
                        $timestampsToUpdate[] = $requiredTimestamp;
                    }
                }

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
                    
                    $this->logger->debug("Totally " . count($intervals) . " fitness record(s) will be updated.", array("intervals" => $intervals));
                    $this->eventPublisher->publish(Event::FitnessActivityDetected($intervals));
                }
            }
        }

        private function getAllRequiredFitnessRecordTimestamps() : array {
            $trips = $this->tripService->getRegularTrips(null, null, null, array(), TripSortingStrategy::OldestAscending);

            $allTimestamps = array();
            foreach ($trips as &$trip) {
                $currentTimestamp = $trip->getStart() - ($trip->getStart() % CommonConstants::FITNESS_RECORD_DURATION_SECONDS);
                while ($currentTimestamp + CommonConstants::FITNESS_RECORD_DURATION_SECONDS
                    <= min(time(), $trip->getEnd() - ($trip->getEnd() % CommonConstants::FITNESS_RECORD_DURATION_SECONDS) + CommonConstants::FITNESS_RECORD_DURATION_SECONDS)) {
                    $allTimestamps[] = $currentTimestamp;
                    $currentTimestamp += CommonConstants::FITNESS_RECORD_DURATION_SECONDS;
                }
            }

            return array_unique($allTimestamps);
        }
    }
?>