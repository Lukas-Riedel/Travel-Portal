<?php
    namespace Core\Service\Task;

    use Common\Client\Cache\CacheClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Trip\TripService;

    class TaskService {

        private const PERSISTENT_NOTIFICATION_CACHE_KEY_FORMAT = "TaskService:Notification:%s";
        private const EPHEMERAL_NOTIFICATION_CACHE_KEY_FORMAT = "TaskService:Notification:%s:%s:%s";

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private readonly TaskMapper $taskMapper;
        private readonly CacheClient $distributedCacheClient;
        private readonly ConfigurationService $configurationService;
        
        private ?TripService $tripService = null;

        private array $ephemeralTaskProviders = array();

        public function __construct(DatabaseClient $databaseClient, CacheClient $distributedCacheClient, ConfigurationService $configurationService) {
            $this->taskMapper = new TaskMapper($databaseClient);
            $this->distributedCacheClient = $distributedCacheClient;
            $this->configurationService = $configurationService;
        }

        public function setTripService(TripService $tripService) : void {
            $this->tripService = $tripService;
        }

        public function setEphemeralTaskProviders(array $ephemeralTaskProviders) : void {
            $this->ephemeralTaskProviders = $ephemeralTaskProviders;
        }

        public function createTask(string $title, ?string $description, TaskPriority $priority, ?int $deadline, ?int $notificationInterval, bool $autoDelete, string $tripId) : Task {
            $task = new Task(null, $title, $description, $priority, $deadline, $notificationInterval, null, true, $autoDelete);
            $this->taskMapper->insertTask($task, $tripId);
            return $task;
        }

        public function getTripIdForTask(string $taskId) : ?string {
            return $this->taskMapper->selectTripIdForTask($taskId);
        }

        public function getTask(string $taskId, string $tripId) : ?Task {
            return $this->taskMapper->selectTask($taskId, $tripId);
        }

        public function getTasks(string $tripId) : array {
            $tasks = array_merge($this->taskMapper->selectTasks($tripId), $this->getEphemeralTasks($tripId));

            usort($tasks, function($a, $b) {
                $priorityDiff = $b->getPriority()->toNumber() - $a->getPriority()->toNumber();
                if ($priorityDiff !== 0) {
                    return $priorityDiff;
                }

                if ($a->getDeadline() === null && $b->getDeadline() === null) {
                    return 0;
                }

                if ($a->getDeadline() === null) {
                    return 1;
                }

                if ($b->getDeadline() === null) {
                    return -1;
                }

                return $a->getDeadline() - $b->getDeadline();
            });

            return $tasks;
        }

        public function getTasksForNotifications() : array {
            $tasks = array();

            foreach ($this->taskMapper->selectTasksForNotifications() as &$task) {
                if ($task->isAutoDelete()) {
                    $this->taskMapper->deleteTask($task->getId(), null);
                    $tasks[] = $task;
                }
                else {
                    $ttl = $this->getNotificationTtl($task->getNotificationInterval(), $this->taskMapper->selectTripIdForTask($task->getId()));
                    $cacheKey = sprintf(self::PERSISTENT_NOTIFICATION_CACHE_KEY_FORMAT, $task->getId());
                    if ($this->distributedCacheClient->trySet($cacheKey, true, $ttl)) {
                        $tasks[] = $task;
                    }
                }

            }

            $ephemeralTasks = $this->doGetEphemeralTasks(null, function($template, $candidate) {
                if (time() + $template["trigger"]["seconds"] < $candidate->getValidity()) {
                    return false;
                }

                $ttl = $this->getNotificationTtl($template["notificationInterval"] ?? null, $candidate->getTripId());
                $cacheKey = sprintf(self::EPHEMERAL_NOTIFICATION_CACHE_KEY_FORMAT, $template["source"], implode(":", $candidate->getPlaceholders()), $template["title"]);
                return $this->distributedCacheClient->trySet($cacheKey, true, $ttl);
            });

            return array_merge($tasks, $ephemeralTasks);
        }

        public function updateTaskTitle(string $taskId, string $title) : bool {
            return $this->taskMapper->updateTaskTitle($taskId, $title) > 0;
        }

        public function updateTaskDescription(string $taskId, ?string $description) : bool {
            return $this->taskMapper->updateTaskDescription($taskId, $description) > 0;
        }

        public function updateTaskPriority(string $taskId, TaskPriority $priority) : bool {
            return $this->taskMapper->updateTaskPriority($taskId, $priority) > 0;
        }

        public function removeTask(string $taskId, string $tripId) : bool {
            return $this->taskMapper->deleteTask($taskId, $tripId) > 0;
        }

        private function getNotificationTtl(?int $notificationInterval, string $tripId) : int {
            if ($notificationInterval !== null) {
                return $notificationInterval;
            }

            $tripEnd = $this->tripService->getRegularTrip($tripId)?->getEnd() ?? time();
            return max($tripEnd - time(), CommonConstants::ONE_DAY_SECONDS);
        }

        private function getEphemeralTasks(?string $tripId) : array {
            return $this->doGetEphemeralTasks($tripId, fn($template, $candidate) => true);
        }

        private function doGetEphemeralTasks(?string $tripId, callable $filter) : array {
            $tasks = array();

            $providersBySource = array();
            foreach ($this->ephemeralTaskProviders as &$provider) {
                $providersBySource[$provider->getSource()] = $provider;
            }

            foreach ($this->configurationService->getConfigurationEntry("ephemeralTasks") as &$template) {
                if (!isset($providersBySource[$template["source"]])) {
                    continue;
                }

                foreach ($providersBySource[$template["source"]]->getCandidates() as &$candidate) {
                    if ($tripId !== null && $candidate->getTripId() !== $tripId) {
                        continue;
                    }

                    if (!$filter($template, $candidate)) {
                        continue;
                    }

                    $tasks[] = new Task(null, $template["title"], $this->createText($template["text"], $candidate->getPlaceholders()),
                                TaskPriority::from($template["priority"]), $candidate->getValidity() - $template["trigger"]["seconds"], $template["notificationInterval"] ?? null,
                                $candidate->getUrl(), $template["actionable"] ?? false, true);
                }
            }

            return $tasks;
        }

        private function createText(string $format, array $context) : string {
            return str_replace(array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)), array_values($context), $format);
        }
    }
?>
