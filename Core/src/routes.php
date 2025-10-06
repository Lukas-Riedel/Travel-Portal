<?php
    use Core\Resource\AirlineResource;
    use Core\Resource\AirportResource;
    use Core\Resource\CategoryResource;
    use Core\Resource\ConfigurationResource;
    use Core\Resource\DeviceResource;
    use Core\Resource\EventResource;
    use Core\Resource\FitnessResource;
    use Core\Resource\SwaggerResource;
    use Core\Resource\FlightResource;
    use Core\Resource\GeocodingResource;
    use Core\Resource\HighlightResource;
    use Core\Resource\LabelResource;
    use Core\Resource\MonitoringResource;
    use Core\Resource\PlaceResource;
    use Core\Resource\StatisticsResource;
    use Core\Resource\TrackerResource;
    use Core\Resource\SubscriptionResource;
    use Core\Resource\TripResource;
    use Core\Resource\YearResource;
    use Slim\App;

    return function (App $app, string $coreBaseUrl) use ($configurationService, $deviceService, $flightService, $categoryService,
        $highlightService, $fitnessService, $geocodingService, $monitoringService, $labelService, $expenseService,
        $statisticsService, $timeTrackingService, $yearService, $tripService, $placeService, $noteService,
        $photoService, $eventPublisher, $logger) {
        ConfigurationResource::register($app, $configurationService);
        DeviceResource::register($app, $deviceService);
        AirlineResource::register($app, $flightService, $logger);
        FlightResource::register($app, $flightService); 
        CategoryResource::register($app, $categoryService, $highlightService, $logger);
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
        SwaggerResource::register($app, $coreBaseUrl);
    };
?>