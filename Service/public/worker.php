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

    register_shutdown_function(function() use ($lock) {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    });

    $eventManager->handleEvents();
?>