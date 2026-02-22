<?php
    use Common\Resource\ManagementResource;
    use Iam\Resource\CertificateResource;
    use Iam\Resource\GoogleResource;
    use Iam\Resource\IbmCloudResource;
    use Iam\Resource\TokenResource;
    use Iam\Resource\UserResource;
    use Slim\App;

    return function(App $app, string $appName, string $versionTag, string $googleApiClientId, string $iamBaseUrl) use($googleService, $ibmCloudService, $certificateService,
        $tokenService, $userService, $authenticationService, $encryptionClient, $healthCheckables) {
        GoogleResource::register($app, $authenticationService, $googleService, $encryptionClient, $googleApiClientId, $iamBaseUrl);
        IbmCloudResource::register($app, $ibmCloudService);
        TokenResource::register($app, $tokenService);
        UserResource::register($app, $userService);
        CertificateResource::register($app, $certificateService);
        ManagementResource::register($app, $appName, $versionTag, $healthCheckables);
    };
?>