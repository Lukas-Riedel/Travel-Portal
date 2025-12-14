<?php
    use Core\Event\Event;

    require_once(__DIR__ . "/bootstrap.php");

    $sql = <<<'SQL'
        SELECT table_name
        FROM information_schema.tables
    SQL;
    
    $tablesToBackup = $databaseClient
        ->statementBuilder($sql)
        ->getResultSetForColumn("table_name");
    
    $eventPublisher->publish(Event::ApplicationStarted($tablesToBackup));
?>