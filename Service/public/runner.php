<?php
    $delayViewMaterializationIfNeeded = FALSE;
    require_once(__DIR__ . "/bootstrap.php");
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };
    set_error_handler($onError);
    
    $key = ftok(__FILE__, 1);
    $semaphore = sem_get($key);

    if (sem_acquire($semaphore, TRUE)) {
        $eventManager->handleEvents();
        sem_release($semaphore);
    }
    
    restore_error_handler();
?>