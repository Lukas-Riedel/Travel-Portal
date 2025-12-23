<?php
    require_once(__DIR__ . "/bootstrap.php");

    pcntl_async_signals(true);

    $eventListener->listen();
?>