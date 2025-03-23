<?php
    require_once(dirname(__FILE__) . "/TimeTrackingMapper.php");
    require_once(dirname(__FILE__) . "/../model/TimeTrackingEvent.php");

    class TimeTrackingService {

        private const CARRIED_OVER_DESCRIPTION = "Carried over from last year";
        private const OPENING_BALANCE_DESCRIPTION = "Opening balance";
        private const BEGINNING_OF_YEAR = "1.1.";

        private const RESET_OPENING_BALANCES_ACTION_NAME = "RESET_OPENING_BALANCES";

        private readonly TimeTrackingMapper $timeTrackingMapper;

        private readonly ConfigurationService $configurationService;

        public function __construct(DatabaseProvider $databaseProvider, ConfigurationService $configurationService) {
            $this->timeTrackingMapper = new TimeTrackingMapper($databaseProvider);
            $this->configurationService = $configurationService;
        }

        // TODO: Replace string $type by TimeTrackingEventType $category.
        public function createTimeTrackingEvent(string $type, float $hours, string $description, int $timestamp) : TimeTrackingEvent {
            $timeTrackingEvent = new TimeTrackingEvent(NULL, $description, $hours, $timestamp, $type,
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

        public function resetOpeningBalances() : void {
            foreach ($this->configurationService->getConfigurationKeysForType("timeOffHours") as &$eventType) {
                $openingBalance = floatval($this->configurationService->getConfigurationForTypeAndKey("timeOffHours", $eventType));

                $carryOverBalance = $this->timeTrackingMapper->selectCarryOverBalanceFromPreviousYears($eventType);
                $wasReset = $this->timeTrackingMapper->deleteTimeTrackingEventsFromPreviousYears($eventType) > 0;

                if ($wasReset) {    
                    if ($carryOverBalance !== NULL && $carryOverBalance > 0) {
                        $this->createTimeTrackingEvent($eventType, $carryOverBalance, self::CARRIED_OVER_DESCRIPTION, $this->getBeginningOfCurrentYear());
                    }
                    
                    if ($openingBalance > 0) {
                        $this->createTimeTrackingEvent($eventType, $openingBalance, self::OPENING_BALANCE_DESCRIPTION, $this->getBeginningOfCurrentYear());
                    }
                }
            }
        }

        private function getBeginningOfCurrentYear() : string {
            return self::BEGINNING_OF_YEAR . date("Y");
        }

        public function onVacationReset(mixed $message) : void {
            $this->resetOpeningBalances();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            global $eventPublisher, $scheduler;

            if ($message["action"] === self::RESET_OPENING_BALANCES_ACTION_NAME) {
                $beginningOfCurrentYearTimestamp = strtotime($this->getBeginningOfCurrentYear());

                // This will keep evaluating to false until the beginning of the next year.
                // Then, it will be eventually executed.
                if ($beginningOfCurrentYearTimestamp < $message["timeSinceLastExecution"]) {
                    $eventPublisher->publishVacationResetEvent();                        
                    $scheduler->recordEventsTriggered(self::RESET_OPENING_BALANCES_ACTION_NAME);
                }
            }
        }
    }

    enum TimeTrackingEventType : string {
        case Vacation = "VACATION";
        case Selfcare = "SELFCARE";
        case Tenure = "TENURE";
        case Overtime = "OVERTIME";
    }
?>