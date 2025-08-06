<?php
    require_once(__DIR__ . "/src/php/bootstrap.php");
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };
    set_error_handler($onError);

    $lock = NULL;
    $lockFile = NULL;

    for ($i = 0; $i < WORKERS_COUNT; ++$i) {
        $path = __DIR__ . "/worker.lock." . $i;
        $handle = fopen($path, "w+");
        if ($handle && flock($handle, LOCK_EX | LOCK_NB)) {
            $lock = $handle;
            $lockFile = $path;
            break;
        } 
        else {
            fclose($handle);
        }
    }

    if ($lock) {
        try {
            $eventManager->handleEvents();
        }
        finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
?>