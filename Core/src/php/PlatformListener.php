<?php
    namespace Core;

    use Core\Client\Calendar\Calendar;
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Client\Google\GoogleClient;

    class PlatformListener {        
        
        private const WATCH_CALENDAR_ACTION_NAME = "WATCH_CALENDAR";
        private const WATCH_CALENDAR_ACTION_INTERVAL = CommonConstants::ONE_DAY_SECONDS - CommonConstants::ONE_HOUR_SECONDS;

        private const BACKUP_ROOT_FOLDER_NAME = "Travel Portal Backups";
        private const BACKUP_FOLDER_NAME_DATE_FORMAT = "d.m.Y H:i:s";

        private readonly DatabaseClient $databaseClient;
        private readonly GoogleClient $googleClient;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseClient $databaseClient, GoogleClient $googleClient, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->databaseClient = $databaseClient;
            $this->googleClient = $googleClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onApplicationStarted(mixed $message) : void {
            $rootBackupFolderId = $this->googleClient->getOrCreateFolderId(self::BACKUP_ROOT_FOLDER_NAME, null);
            $backupFolderId = $this->googleClient->createFolder(date(self::BACKUP_FOLDER_NAME_DATE_FORMAT), $rootBackupFolderId);

            foreach (explode(",", $message["tables"]) as &$table) {
                $tableRows = $this->databaseClient
                    ->statementBuilder("SELECT * FROM {$table}")
                    ->getResultSet();

                $tableDump = array_map(function($row) use (&$table) {
                    $columns = array_map(fn($k) => "`$k`", array_keys($row));
                    $values  = array_map(fn($v) => $v === null ? "null" : "'" . str_replace("'", "''", $v) . "'", array_values($row));

                    return "INSERT INTO {$table} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");";
                }, $tableRows);

                $this->googleClient->createFile("{$table}.sql", $backupFolderId, "application/sql", implode("\n", $tableDump));
            }
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