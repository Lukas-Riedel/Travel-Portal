<?php
    namespace Core\Service\Task;
    
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;

    class TaskMapper {
        
        // TODO: Make this configurable per task?
        private const SEND_NOTIFICATION_INTERVAL = CommonConstants::ONE_DAY_SECONDS;
        
        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectTripIdForTask(string $taskId) : ?string {
            $sql = <<<'SQL'
                SELECT trip_id
                FROM task
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($taskId)
                ->getSingleColumn("trip_id");
        }

        public function selectTask(string $taskId, string $tripId) : ?Task {
            $sql = <<<'SQL'
                SELECT *
                FROM task
                WHERE id = ?
                    AND trip_id = ?
            SQL;

            $taskRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($taskId, $tripId)
                ->getSingleRow();

            if ($taskRow === null) {
                return null;
            }

            return new Task($taskRow["id"], $taskRow["description"], TaskPriority::fromNumber(intval($taskRow["priority"])), $taskRow["deadline"] === null ? null : intval($taskRow["deadline"]));
        }

        public function selectTasks(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM task
                WHERE trip_id = ?
                ORDER BY priority ASC,
                    deadline ASC NULLS LAST
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($taskRow) {
                    return new Task($taskRow["id"], $taskRow["description"], TaskPriority::fromNumber(intval($taskRow["priority"])), $taskRow["deadline"] === null ? null : intval($taskRow["deadline"]));
                });
        }

        public function selectTasksForNotifications() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM task
                WHERE deadline IS NOT NULL AND (
                    (last_notification IS NULL AND deadline < ROUND(EXTRACT(EPOCH FROM NOW())))
                    OR (last_notification IS NOT NULL AND last_notification + ? < ROUND(EXTRACT(EPOCH FROM NOW())))
                )
                ORDER BY priority ASC,
                    deadline ASC NULLS LAST
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(self::SEND_NOTIFICATION_INTERVAL)
                ->getMappedResultSet(function($taskRow) {
                    return new Task($taskRow["id"], $taskRow["description"], TaskPriority::fromNumber(intval($taskRow["priority"])), $taskRow["deadline"] === null ? null : intval($taskRow["deadline"]));
                });
        }

        public function insertTask(Task $task, string $tripId) : bool {
            $sql = <<<'SQL'
                INSERT INTO task (
                    trip_id,
                    description,
                    priority,
                    deadline
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?
                )
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId, $task->getDescription(), $task->getPriority()->toNumber(), $task->getDeadline())
                ->getSingleColumn("id");                 

            if ($id === null) {
                return false;
            }

            $task->setId($id);
            return true;
        }

        public function updateTaskLastNotification(string $taskId, int $lastNotification) : bool {
            $sql = <<<'SQL'
                UPDATE task
                SET last_notification = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($lastNotification, $taskId)
                ->execute() > 0;
        }

        public function updateTaskDescription(string $taskId, string $description) : bool {
            $sql = <<<'SQL'
                UPDATE task
                SET description = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($description, $taskId)
                ->execute() > 0;
        }

        public function updateTaskPriority(string $taskId, TaskPriority $priority) : bool {
            $sql = <<<'SQL'
                UPDATE task
                SET priority = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($priority->toNumber(), $taskId)
                ->execute() > 0;
        }

        public function deleteTask(string $taskId, string $tripId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM task
                WHERE id = ?
                    AND trip_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($taskId, $tripId)
                ->execute();
        }
    }
?>