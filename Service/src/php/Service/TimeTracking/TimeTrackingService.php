<?php
    namespace Service\Service\TimeTracking;

    use Service\Service\Configuration\ConfigurationService;

    class TimeTrackingService {

        private const CARRIED_OVER_DESCRIPTION = "Carried over from last year";
        private const OPENING_BALANCE_DESCRIPTION = "Opening balance";

        private readonly TimeTrackingMapper $timeTrackingMapper;

        private readonly ConfigurationService $configurationService;

        public function __construct(\DatabaseProvider $databaseProvider, ConfigurationService $configurationService) {
            $this->timeTrackingMapper = new TimeTrackingMapper($databaseProvider);
            $this->configurationService = $configurationService;
        }

        // TODO: Replace string $type by TimeTrackingEventType $category.
        public function createTimeTrackingEvent(string $type, float $hours, string $description, int $timestamp) : TimeTrackingEvent {
            $timeTrackingEvent = new TimeTrackingEvent(NULL, $description, $hours, $timestamp, TimeTrackingEventType::from($type),
                $hours + $this->timeTrackingMapper->selectBalance($type, $timestamp));
            $this->timeTrackingMapper->insertTimeTrackingEvent($timeTrackingEvent);
            return $timeTrackingEvent;
        }

        // TODO: Replace string $type by TimeTrackingEventType $category.
        public function getTimeTrackingEvents(?string $type = NULL) : array {  
            return $this->timeTrackingMapper->selectTimeTrackingEvents($type);
        }

        public function removeTimeTrackingEvent(string $eventId) : bool {
            return $this->timeTrackingMapper->deleteTimeTrackingEvent($eventId) > 0;
        }

        public function executeTimeTrackingEventsAudit() : void {
            $this->timeTrackingMapper->deleteStalePlannedWorkEvents();
            $this->timeTrackingMapper->deleteUsedOvertimeEvents();
        }

        public function resetOpeningBalances(string $beginningOfYearDate) : void {
            foreach ($this->configurationService->getConfigurationEntry("timeTracking")["timeOffHours"] as $eventType => $openingBalance) {
                $carryOverBalance = $this->timeTrackingMapper->selectCarryOverBalanceFromPreviousYears($eventType);
                $wasReset = $this->timeTrackingMapper->deleteTimeTrackingEventsFromPreviousYears($eventType) > 0;

                if ($wasReset) {    
                    if ($carryOverBalance !== NULL && $carryOverBalance > 0) {
                        $this->createTimeTrackingEvent($eventType, $carryOverBalance, self::CARRIED_OVER_DESCRIPTION, $beginningOfYearDate);
                    }
                    
                    if ($openingBalance > 0) {
                        $this->createTimeTrackingEvent($eventType, $openingBalance, self::OPENING_BALANCE_DESCRIPTION, $beginningOfYearDate);
                    }
                }
            }
        }
    }
?>