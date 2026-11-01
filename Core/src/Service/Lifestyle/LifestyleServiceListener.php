<?php
    namespace Core\Service\Lifestyle;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Configuration\ConfigurationService;
    use Monolog\Logger;

    class LifestyleServiceListener {

        private const FETCH_LIFESTYLE_ACTION_NAME = "FETCH_LIFESTYLE";
        private const FETCH_LIFESTYLE_ACTION_INTERVAL = 2 * CommonConstants::ONE_HOUR_SECONDS;
        
        // Only around 100 intervals fit into the size limit of 4kB for an FCM message.
        private const INTERVALS_LIMIT = 100;

        private readonly LifestyleService $lifestyleService;
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly Logger $logger;
        
        private readonly int $lifestyleRetentionDays;

        public function __construct(LifestyleService $lifestyleService, ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger, int $lifestyleRetentionDays) {
            $this->lifestyleService = $lifestyleService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
            $this->lifestyleRetentionDays = $lifestyleRetentionDays;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            $lifestyleConfiguration = $this->configurationService->getConfigurationEntry("lifestyle");
            if (!$lifestyleConfiguration["trackingEnabled"]) {
                return;
            }

            if ($this->scheduler->requestExecution(self::FETCH_LIFESTYLE_ACTION_NAME, self::FETCH_LIFESTYLE_ACTION_INTERVAL)) {
                $basalMetabolismEvents = $this->lifestyleService->getLifestyleEvents(LifestyleEventType::BasalMetabolism, null, null);
                $stepEvents = $this->lifestyleService->getLifestyleEvents(LifestyleEventType::Steps, null, null);

                $basalMetabolismTimestamps = array_flip(array_map(fn($event) => $event->getTimestamp(), $basalMetabolismEvents));
                $stepTimestamps = array_flip(array_map(fn($event) => $event->getTimestamp(), $stepEvents));
                
                $validTimestamps = array_intersect_key($basalMetabolismTimestamps, $stepTimestamps);
                $allRequiredTimestamps = $this->getAllRequiredBasalMetabolismTimestamps();
                
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
                            "end" => $timestampToUpdate + CommonConstants::LIFESTYLE_RECORD_DURATION_SECONDS
                        );

                        if (count($intervals) >= self::INTERVALS_LIMIT) {
                            // Other intervals will eventually be fetched in the next scheduler trigger.
                            $this->logger->warning("There are " . count($timestampsToUpdate) . " records to update but only " . self::INTERVALS_LIMIT . "  can be updated at once.", 
                                array("timestamps" => $timestampsToUpdate));
                            break;
                        }
                    }
                    
                    $this->logger->debug("Totally " . count($intervals) . " lifestyle record(s) will be updated.", array("intervals" => $intervals));
                    $this->eventPublisher->publish(Event::LifestyleActivityDetected($intervals, $lifestyleConfiguration["birthYear"], $lifestyleConfiguration["height"], $lifestyleConfiguration["male"]));
                }
            }
        }

        private function getAllRequiredBasalMetabolismTimestamps() : array {
            $allTimestamps = array();

            $now = time();
            $currentTimestamp = $now - ($now % CommonConstants::LIFESTYLE_RECORD_DURATION_SECONDS) - CommonConstants::LIFESTYLE_RECORD_DURATION_SECONDS;
            for ($i = 0; $i < $this->lifestyleRetentionDays * (CommonConstants::ONE_DAY_SECONDS / CommonConstants::LIFESTYLE_RECORD_DURATION_SECONDS); ++$i) {
                $allTimestamps[] = $currentTimestamp;
                $currentTimestamp -= CommonConstants::LIFESTYLE_RECORD_DURATION_SECONDS;
            }

            return $allTimestamps;
        }
    }
?>
