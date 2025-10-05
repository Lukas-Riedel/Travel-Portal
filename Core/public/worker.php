<?php
    require_once(__DIR__ . "/src/bootstrap.php");

    $lockKeyFormat = "Worker:Lock:%s";
    $lock = NULL;
    
    try {
        for ($i = 0; $i < MAX_WORKERS_COUNT; ++$i) {
            $lock = $cacheClient->tryLock(sprintf($lockKeyFormat, $i), round(0.95 * (int)ini_get("max_execution_time")));
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