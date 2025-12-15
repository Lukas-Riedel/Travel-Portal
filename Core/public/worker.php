<?php
    require_once(__DIR__ . "/../src/bootstrap.php");

    $defaultLockTtl = 1800;
    $maxExecutionTime = (int) ini_get("max_execution_time");
    
    // When changing the lock key format, change also in deploy.php.
    $lockKeyFormat = "Worker:Lock:%s";

    $lock = NULL;    
    $i = 0; 

    try {
        for (; $i < getenv("MAX_WORKERS_COUNT"); ++$i) {
            $lock = $cacheClient->tryLock(sprintf($lockKeyFormat, $i), $maxExecutionTime > 0 ? round(0.95 * $maxExecutionTime) : $defaultLockTtl);
            if ($lock !== NULL) {
                break;
            }
        }

        if ($lock === NULL) {
            $logger->debug("Worker will not be started because no lock could be acquired.");
            exit(0);
        }

        $logger->debug("Starting Worker $i...");
        $eventListener->listen();
    }
    finally {
        if ($lock !== NULL) {
            $logger->debug("Terminating Worker $i...");
            $lock->unlock();
        }
    }
?>