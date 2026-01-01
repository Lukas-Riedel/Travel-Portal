<?php
    namespace Core\Service\TimeTracking;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;

    class TimeTrackingService {

        private const CARRIED_OVER_DESCRIPTION = "Carried over from last year";
        private const OPENING_BALANCE_DESCRIPTION = "Opening balance";

        private readonly TimeTrackingMapper $timeTrackingMapper;

        private readonly ConfigurationService $configurationService;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService) {
            $this->timeTrackingMapper = new TimeTrackingMapper($databaseClient);
            $this->configurationService = $configurationService;
            $this->transactionManager = $databaseClient;
        }

        // TODO: Replace string $type by TimeTrackingEventType $type.
        public function createTimeTrackingEvent(string $type, float $hours, string $description, int $timestamp) : TimeTrackingEvent {
            $timeTrackingEvent = new TimeTrackingEvent(null, $description, $hours, $timestamp, TimeTrackingEventType::from($type),
                $hours + $this->timeTrackingMapper->selectBalance($type, $timestamp));
            $this->timeTrackingMapper->insertTimeTrackingEvent($timeTrackingEvent);
            return $timeTrackingEvent;
        }

        // TODO: Replace string $type by TimeTrackingEventType $type.
        public function getTimeTrackingEvents(?string $type = null) : array {  
            return $this->timeTrackingMapper->selectTimeTrackingEvents($type);
        }

        public function removeTimeTrackingEvent(string $eventId) : bool {
            return $this->timeTrackingMapper->deleteTimeTrackingEvent($eventId) > 0;
        }

        public function executeTimeTrackingEventsAudit() : void {
            $this->timeTrackingMapper->deleteStalePlannedWorkEvents();
            $this->timeTrackingMapper->deleteUsedOvertimeEvents();
        }

        public function resetOpeningBalances(int $beginningOfYearTimestamp) : void {
            foreach ($this->configurationService->getConfigurationEntry("timeTracking")["timeOffHours"] as $eventType => $openingBalance) {
                $carryOverBalance = $this->timeTrackingMapper->selectCarryOverBalanceFromPreviousYears($eventType);                
                $this->transactionManager->executeAtomically(function() use(&$eventType, &$carryOverBalance, &$openingBalance, &$beginningOfYearTimestamp) {
                    $wasReset = $this->timeTrackingMapper->deleteTimeTrackingEventsFromPreviousYears($eventType) > 0;

                    if ($wasReset) {    
                        if ($carryOverBalance !== null && $carryOverBalance > 0) {
                            $this->createTimeTrackingEvent($eventType, $carryOverBalance, self::CARRIED_OVER_DESCRIPTION, $beginningOfYearTimestamp);
                        }
                        
                        if ($openingBalance > 0) {
                            $this->createTimeTrackingEvent($eventType, $openingBalance, self::OPENING_BALANCE_DESCRIPTION, $beginningOfYearTimestamp);
                        }
                    }
                });
            }
        }
    }
?>