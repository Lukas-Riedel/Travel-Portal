<?php
    $delayViewMaterializationIfNeeded = FALSE;
    require_once(dirname(__FILE__) . "/bootstrap.php");

    $scheduler->schedule();
?>