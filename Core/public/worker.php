<?php
    require_once(__DIR__ . "/src/bootstrap.php");
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };
    set_error_handler($onError);

    $lockKeyPrefix = "Worker:Lock";
    $lock = NULL;
    
    try {
        for ($i = 0; $i < MAX_WORKERS_COUNT; ++$i) {
            $lock = $cacheClient->tryLock($lockKeyPrefix . ":" . $i, round(0.95 * (int)ini_get("max_execution_time")));
            if ($lock !== NULL) {
                break;
            }
        }

        if ($lock === NULL) {
            exit(0);
        }

        $eventListener->listen();
    }
    finally {
        if ($lock !== NULL) {
            $lock->unlock();
        }
    }
?>