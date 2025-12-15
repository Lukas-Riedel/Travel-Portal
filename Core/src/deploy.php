<?php
    use Core\Event\Event;

    require_once(__DIR__ . "/bootstrap.php");
    
    $lockKeyFormat = "Worker:Lock:%s";
    for ($i = 0; $i < getenv("MAX_WORKERS_COUNT"); ++$i) {
        $cacheClient->delete(sprintf($lockKeyFormat, $i));
    }

    $sql = <<<'SQL'
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
    SQL;
    
    $tablesToBackup = $databaseClient
        ->statementBuilder($sql)
        ->getResultSetForColumn("table_name");
    
    $eventPublisher->publish(Event::ApplicationStarted($tablesToBackup));
?>