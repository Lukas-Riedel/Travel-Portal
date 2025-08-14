<?php

    use Core\Resource\AirlineResource;
    use Core\Resource\CategoryResource;
    use Core\Resource\ConfigurationResource;
    use Core\Resource\DeviceResource;
    use Core\Resource\FitnessResource;
    use Core\Resource\SwaggerResource;
    use Core\Resource\FlightResource;
    use Core\Resource\GeocodingResource;
    use Core\Resource\MonitoringResource;
    use Slim\App;

    return function (App $app) use ($configurationService, $deviceService, $flightService, $categoryService,
        $highlightService, $fitnessService, $geocodingService, $monitoringService) {
        ConfigurationResource::register($app, $configurationService);
        DeviceResource::register($app, $deviceService);
        AirlineResource::register($app, $flightService);
        FlightResource::register($app, $flightService); 
        CategoryResource::register($app, $categoryService, $highlightService);
        FitnessResource::register($app, $fitnessService);
        GeocodingResource::register($app, $geocodingService);
        MonitoringResource::register($app, $monitoringService);
        SwaggerResource::register($app);
    };
?>