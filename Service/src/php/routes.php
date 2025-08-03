<?php
    use Service\Resource\ConfigurationResource;
    use Service\Resource\DeviceResource;
    use Service\Resource\SwaggerResource;
    use Slim\App;

    return function (App $app) use ($configurationService, $deviceService) {
        ConfigurationResource::register($app, $configurationService);
        DeviceResource::register($app, $deviceService);
        SwaggerResource::register($app);
    };
?>