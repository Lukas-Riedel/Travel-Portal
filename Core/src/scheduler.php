<?php
    require_once(__DIR__ . "/bootstrap.php");
    
    $logger->pushProcessor(function($record) use(&$loggingContext) {
        $record["context"]["transaction_id"] = $loggingContext->getTransactionId();
        $record["extra"]["transaction_id"] = $loggingContext->getTransactionId();
        return $record;
    });

    $scheduler->schedule();    
?>