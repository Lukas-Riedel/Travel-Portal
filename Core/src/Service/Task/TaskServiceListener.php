<?php
    namespace Core\Service\Task;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Trip\TripService;

    class TaskServiceListener {
        
        private const SEND_TASK_NOTIFICATIONS_ACTION_NAME = "SEND_TASK_NOTIFICATIONS";
        private const SEND_TASK_NOTIFICATIONS_ACTION_INTERVAL = 300;

        private const TASK_NOTIFICATION_FORMAT = "%s: %s";
        
        private readonly TaskService $taskService;
        private readonly TripService $tripService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(TaskService $taskService, TripService $tripService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->taskService = $taskService;
            $this->tripService = $tripService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::SEND_TASK_NOTIFICATIONS_ACTION_NAME, self::SEND_TASK_NOTIFICATIONS_ACTION_INTERVAL)) {
                foreach ($this->taskService->getTasksForNotifications() as &$task) {
                    $trip = $this->tripService->getRegularTrip($this->taskService->getTripIdForTask($task->getId()));
                    $this->eventPublisher->publish(Event::TaskDeadlineReached(sprintf(self::TASK_NOTIFICATION_FORMAT, $trip->getFullName(), $task->getDescription())));
                    $this->taskService->resetTaskLastNotification($task->getId());
                }       
            }
        }
    }
?>