<?php
    namespace Core\Service\Task;
    
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\WhereClauseBuilder;

    class TaskMapper {
        
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

            return new Task($taskRow["id"], $taskRow["title"], $taskRow["description"], TaskPriority::fromNumber(intval($taskRow["priority"])), $taskRow["deadline"] === null ? null : intval($taskRow["deadline"]), $taskRow["notification_interval"] === null ? null : intval($taskRow["notification_interval"]), null, true, $taskRow["auto_delete"] === "t");
        }

        public function selectTasks(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM task
                WHERE trip_id = ?
                ORDER BY priority DESC,
                    deadline ASC NULLS LAST
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($taskRow) {
                    return new Task($taskRow["id"], $taskRow["title"], $taskRow["description"], TaskPriority::fromNumber(intval($taskRow["priority"])), $taskRow["deadline"] === null ? null : intval($taskRow["deadline"]), $taskRow["notification_interval"] === null ? null : intval($taskRow["notification_interval"]), null, true, $taskRow["auto_delete"] === "t");
                });
        }

        public function selectTasksForNotifications() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM task
                WHERE deadline IS NOT NULL
                    AND deadline < ROUND(EXTRACT(EPOCH FROM NOW()))
                ORDER BY priority DESC,
                    deadline ASC NULLS LAST
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($taskRow) {
                    return new Task($taskRow["id"], $taskRow["title"], $taskRow["description"], TaskPriority::fromNumber(intval($taskRow["priority"])), $taskRow["deadline"] === null ? null : intval($taskRow["deadline"]), $taskRow["notification_interval"] === null ? null : intval($taskRow["notification_interval"]), null, true, $taskRow["auto_delete"] === "t");
                });
        }

        public function insertTask(Task $task, string $tripId) : bool {
            $sql = <<<'SQL'
                INSERT INTO task (
                    trip_id,
                    title,
                    description,
                    priority,
                    deadline,
                    notification_interval,
                    auto_delete
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId, $task->getTitle(), $task->getDescription(), $task->getPriority()->toNumber(), $task->getDeadline(), $task->getNotificationInterval(), $task->isAutoDelete() ? "true" : "false")
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $task->setId($id);
            return true;
        }

        public function updateTaskTitle(string $taskId, string $title) : bool {
            $sql = <<<'SQL'
                UPDATE task
                SET title = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($title, $taskId)
                ->execute() > 0;
        }

        public function updateTaskDescription(string $taskId, ?string $description) : bool {
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

        public function deleteTask(string $taskId, ?string $tripId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM task
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = (new WhereClauseBuilder())->withClause("id = ?", $taskId);
            if ($tripId !== null) {
                $whereClauseBuilder->withClause("trip_id = ?", $tripId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->execute();
        }
    }
?>