<?php
    namespace Core\Service\Task;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class TaskServiceListener {
        
        private const SEND_TASK_NOTIFICATIONS_ACTION_NAME = "SEND_TASK_NOTIFICATIONS";
        private const SEND_TASK_NOTIFICATIONS_ACTION_INTERVAL = 300;
        
        private readonly TaskService $taskService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(TaskService $taskService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->taskService = $taskService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::SEND_TASK_NOTIFICATIONS_ACTION_NAME, self::SEND_TASK_NOTIFICATIONS_ACTION_INTERVAL)) {
                foreach ($this->taskService->getTasksForNotifications() as &$task) {
                    $this->eventPublisher->publish(Event::TaskDeadlineReached($task->getDescription()));
                    $this->taskService->resetTaskLastNotification($task->getId());
                }       
            }
        }
    }
?>