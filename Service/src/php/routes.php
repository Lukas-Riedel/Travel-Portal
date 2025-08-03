<?php

    use Service\Resource\AirlineResource;
    use Service\Resource\CategoryResource;
    use Service\Resource\ConfigurationResource;
    use Service\Resource\DeviceResource;
    use Service\Resource\SwaggerResource;
    use Service\Resource\FlightResource;
    use Slim\App;

    return function (App $app) use ($configurationService, $deviceService, $flightService, $categoryService, $highlightService) {
        ConfigurationResource::register($app, $configurationService);
        DeviceResource::register($app, $deviceService);
        AirlineResource::register($app, $flightService);
        FlightResource::register($app, $flightService); 
        CategoryResource::register($app, $categoryService, $highlightService);
        SwaggerResource::register($app);
    };
?>