<?php
    use Iam\Resource\GoogleResource;
    use Iam\Resource\IbmCloudResource;
    use Iam\Resource\TokenResource;
    use Iam\Resource\UserResource;
    use Slim\App;

    return function (App $app) use ($googleService, $ibmCloudService, $tokenService, $userService, $authenticationService, $encryptionClient) {
        GoogleResource::register($app, $authenticationService, $googleService, $encryptionClient);
        IbmCloudResource::register($app, $ibmCloudService);
        TokenResource::register($app, $tokenService);
        UserResource::register($app, $userService);
    };
?>