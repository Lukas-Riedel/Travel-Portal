<?php
    require_once(dirname(__FILE__) . "/config/secrets.php");
    require_once(dirname(__FILE__) . "/Provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/Provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/Event/EventPublisher.php");
    require_once(dirname(__FILE__) . "/Event/Scheduler.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $eventPublisher = new EventPublisher();
    $scheduler = new Scheduler($databaseProvider, $eventPublisher);

    $scheduler->schedule();
?>