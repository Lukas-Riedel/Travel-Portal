<?php
    namespace Core;

    use Core\Client\Calendar\Calendar;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class PlatformListener {        
        
        private const WATCH_CALENDAR_ACTION_NAME = "WATCH_CALENDAR";
        private const WATCH_CALENDAR_ACTION_INTERVAL = CommonConstants::ONE_DAY_SECONDS - CommonConstants::ONE_HOUR_SECONDS;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        // TODO: Split to individual services.
        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::WATCH_CALENDAR_ACTION_NAME, self::WATCH_CALENDAR_ACTION_INTERVAL)) {
                foreach (Calendar::cases() as &$calendar) {
                    $this->eventPublisher->publish(Event::CalendarWatchRenewing($calendar->value)); 
                }
            }
        }
    }
?>