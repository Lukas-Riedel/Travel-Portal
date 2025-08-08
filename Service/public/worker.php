<?php
    require_once(__DIR__ . "/src/php/bootstrap.php");
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };
    set_error_handler($onError);

    $lockKeyPrefix = "Worker:Lock";
    $lockTtl = (int)ini_get("max_execution_time");

    $lockKey = NULL;
    $lockValue = uniqid("", TRUE);
    $lockAcquired = FALSE;
    
    try {
        for ($i = 0; $i < MAX_WORKERS_COUNT; ++$i) {
            $lockKey = $lockKeyPrefix . ":" . $i;
            $acquired = $cacheClient->trySet($lockKey, $lockValue, $lockTtl);

            if ($acquired) {
                $lockAcquired = TRUE;
                break;
            }
        }

        if (!$lockAcquired) {
            exit(0);
        }

        $eventManager->handleEvents();
    }
    finally {
        if ($lockAcquired && $lockKey) {
            $cacheClient->delete($lockKey);
        }
    }
?>