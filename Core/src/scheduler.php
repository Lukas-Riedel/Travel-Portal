<?php

    use Ramsey\Uuid\Uuid;

    require_once(__DIR__ . "/bootstrap.php");
    
    $transactionId = Uuid::uuid4()->toString();
    $logger->pushProcessor(function($record) use($transactionId) {
        $record["context"]["transaction_id"] = $transactionId;
        $record["extra"]["transaction_id"] = $transactionId;
        return $record;
    });

    $scheduler->schedule();
    
?>