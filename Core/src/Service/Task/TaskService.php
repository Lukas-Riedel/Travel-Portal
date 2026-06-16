<?php
    namespace Core\Service\Task;
    
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;

    class TaskService {

        private readonly TaskMapper $taskMapper;
        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient) {
            $this->taskMapper = new TaskMapper($databaseClient);
            $this->transactionManager = $databaseClient;
        }

        public function createTask(string $description, TaskPriority $priority, ?int $deadline, string $tripId) : Task {
            $task = new Task(null, $description, $priority, $deadline);
            $this->taskMapper->insertTask($task, $tripId);
            return $task;
        }

        public function getTask(string $taskId, string $tripId) : ?Task {
            return $this->taskMapper->selectTask($taskId, $tripId);
        }

        public function getTasks(string $tripId) : array {
            return $this->taskMapper->selectTasks($tripId);
        }

        public function getTasksForNotifications() : array {
            return $this->taskMapper->selectTasksForNotifications();
        }

        public function updateTaskDescription(string $taskId, string $description) : bool {
            return $this->taskMapper->updateTaskDescription($taskId, $description) > 0;
        }

        public function updateTaskPriority(string $taskId, TaskPriority $priority) : bool {
            return $this->taskMapper->updateTaskPriority($taskId, $priority) > 0;
        }

        public function resetTaskLastNotification(string $taskId) : bool {
            return $this->taskMapper->updateTaskLastNotification($taskId, time()) > 0;
        }

        public function removeTask(string $taskId, string $tripId) : bool {
            return $this->taskMapper->deleteTask($taskId, $tripId) > 0;
        }
    }
?>