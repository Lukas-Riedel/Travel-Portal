<?php
    use Service\Resource\ConfigurationResource;
    use Service\Resource\SwaggerResource;
    use Slim\App;

    return function (App $app) use ($configurationService) {
        ConfigurationResource::register($app, $configurationService);
        SwaggerResource::register($app);
    };
?>