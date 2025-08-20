<?php
    use Core\Resource\AirlineResource;
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

    return function (App $app) use ($configurationService, $deviceService, $flightService, $categoryService,
        $highlightService, $fitnessService, $geocodingService, $monitoringService, $labelService, $expenseService,
        $statisticsService, $timeTrackingService, $yearService, $tripService, $placeService, $noteService,
        $photoService, $eventPublisher) {
        ConfigurationResource::register($app, $configurationService);
        DeviceResource::register($app, $deviceService);
        AirlineResource::register($app, $flightService);
        FlightResource::register($app, $flightService); 
        CategoryResource::register($app, $categoryService, $highlightService);
        FitnessResource::register($app, $fitnessService);
        GeocodingResource::register($app, $geocodingService);
        MonitoringResource::register($app, $monitoringService);
        EventResource::register($app, $eventPublisher);
        HighlightResource::register($app, $highlightService);
        LabelResource::register($app, $labelService);
        StatisticsResource::register($app, $statisticsService);
        TrackerResource::register($app, $timeTrackingService);
        SubscriptionResource::register($app, $expenseService);
        YearResource::register($app, $yearService, $highlightService);
        TripResource::register($app, $tripService, $expenseService, $noteService, $highlightService);
        PlaceResource::register($app, $placeService, $photoService, $labelService, $noteService, $highlightService);
        SwaggerResource::register($app);
    };
?>