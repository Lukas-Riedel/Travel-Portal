<?php
    use Core\Resource\AirlineResource;
    use Core\Resource\AirportResource;
    use Core\Resource\CategoryResource;
    use Core\Resource\ConfigurationResource;
    use Core\Resource\DeviceResource;
    use Core\Resource\DocumentResource;
    use Core\Resource\EventResource;
    use Core\Resource\FitnessResource;
    use Core\Resource\SwaggerResource;
    use Core\Resource\FlightResource;
    use Core\Resource\GeocodingResource;
    use Core\Resource\HighlightResource;
    use Core\Resource\LabelResource;
    use Common\Resource\ManagementResource;
    use Core\Resource\MonitoringResource;
    use Core\Resource\PlaceResource;
    use Core\Resource\RegionResource;
    use Core\Resource\SearchResource;
    use Core\Resource\StatisticsResource;
    use Core\Resource\TrackerResource;
    use Core\Resource\SubscriptionResource;
    use Core\Resource\TripResource;
    use Core\Resource\VoucherResource;
    use Core\Resource\YearResource;
    use Slim\App;

    return function(App $app, string $appName, string $versionTag, string $coreBaseUrl) use($configurationService, $deviceService, $flightService, $categoryService,
        $highlightService, $fitnessService, $geocodingService, $monitoringService, $labelService, $expenseService, $statisticsService, $timeTrackingService, $indexService,
        $yearService, $tripService, $placeService, $noteService, $documentService, $photoService, $eventPublisher, $logger, $healthCheckables) {
        ConfigurationResource::register($app, $configurationService);
        DeviceResource::register($app, $deviceService);
        AirlineResource::register($app, $flightService, $logger);
        FlightResource::register($app, $flightService); 
        CategoryResource::register($app, $categoryService, $highlightService, $logger);
        RegionResource::register($app, $categoryService);
        FitnessResource::register($app, $fitnessService);
        GeocodingResource::register($app, $geocodingService);
        MonitoringResource::register($app, $monitoringService);
        EventResource::register($app, $eventPublisher);
        HighlightResource::register($app, $highlightService, $logger);
        LabelResource::register($app, $labelService, $logger);
        StatisticsResource::register($app, $statisticsService);
        TrackerResource::register($app, $timeTrackingService);
        SubscriptionResource::register($app, $expenseService);
        YearResource::register($app, $yearService, $highlightService, $logger);
        TripResource::register($app, $tripService, $expenseService, $noteService, $highlightService, $logger);
        PlaceResource::register($app, $placeService, $photoService, $labelService, $noteService, $highlightService, $logger);
        AirportResource::register($app, $flightService, $logger);
        DocumentResource::register($app, $documentService);
        VoucherResource::register($app, $expenseService);
        SearchResource::register($app, $indexService, $categoryService, $placeService, $flightService, $labelService, $tripService, $yearService, $photoService, $highlightService);
        SwaggerResource::register($app, $coreBaseUrl);
        ManagementResource::register($app, $appName, $versionTag, $healthCheckables);
    };
?>