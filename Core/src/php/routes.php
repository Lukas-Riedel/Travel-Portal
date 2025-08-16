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
use Core\Resource\MonitoringResource;
    use Slim\App;

    return function (App $app) use ($configurationService, $deviceService, $flightService, $categoryService,
        $highlightService, $fitnessService, $geocodingService, $monitoringService, $eventPublisher) {
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
        SwaggerResource::register($app);
    };
?>