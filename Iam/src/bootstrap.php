<?php
    require_once(__DIR__ . "/../vendor/autoload.php");

    use Common\Client\Cache\RedisCacheClient;
    use Common\Service\Authentication\AuthenticationService;
    use Common\Client\Http\HttpClient;
    use Common\Client\Encryption\EncryptionClient;
    use Common\LoggingContext;
    use Iam\Service\Certificate\CertificateService;
    use Iam\Service\Google\GoogleService;
    use Iam\Service\IbmCloud\IbmCloudService;
    use Iam\Service\Token\TokenService;
    use Iam\Service\User\UserService;
    use Itspire\MonologLoki\Handler\LokiHandler;
    use Monolog\Handler\WhatFailureGroupHandler;
    use Monolog\Logger;

    $onError = function($level, $message, $file, $line) {
        throw new \ErrorException($message);
    };
    set_error_handler($onError);

    // Logger.
    $loggingContext = new LoggingContext();
    $logger = new Logger(getenv("APP_NAME"));
    $handler = new WhatFailureGroupHandler(array(
        new LokiHandler(array(
            "entrypoint" => getenv("GRAFANA_LOKI_ENTRYPOINT"),
            "labels" => array(
                "service" => getenv("APP_NAME"),
                "version_tag" => getenv("VERSION_TAG")
            ),
            "client_name" => getenv("GRAFANA_LOKI_CLIENT_NAME"),
            "auth" => array(
                "basic" => array(
                    getenv("GRAFANA_LOKI_USER"),
                    getenv("GRAFANA_LOKI_PASSWORD")
                )
            )
        ))
    ));
    $logger->pushHandler($handler);

    // Clients.
    $distributedCacheClient = new RedisCacheClient(getenv("REDIS_HOST"), getenv("REDIS_PORT"), getenv("REDIS_PASSWORD"));
    $httpClient = new HttpClient(getenv("APP_NAME"), $loggingContext, $logger);
    $encryptionClient = new EncryptionClient(getenv("ENCRYPTION_PRIVATE_KEY"));
    $healthCheckables = array(
        $distributedCacheClient
    );

    // Services.
    $tokenService = new TokenService($httpClient, getenv("IAM_APP_CLIENT_ID"), getenv("INTERNAL_IAM_BASE_URL"));
    $userService = new UserService($tokenService, $httpClient, getenv("IAM_APP_CLIENT_ID"), getenv("IAM_BACKEND_CLIENT_ID"), getenv("IAM_BACKEND_CLIENT_SECRET"), getenv("INTERNAL_ADMIN_IAM_BASE_URL"));
    $googleService = new GoogleService($userService, $httpClient, $encryptionClient, getenv("FCM_PROJECT_ID"), getenv("FCM_PRIVATE_KEY_ID"), getenv("FCM_PRIVATE_KEY"), getenv("FCM_CLIENT_EMAIL"),
        getenv("FCM_CLIENT_ID"), getenv("GOOGLE_API_CLIENT_ID"), getenv("FCM_CLIENT_X509_CERTIFICATE_URL"), getenv("GOOGLE_API_CLIENT_SECRET"), getenv("IAM_BASE_URL"));
    $ibmCloudService = new IbmCloudService($httpClient, getenv("IBM_CLOUD_IAM_BASE_URL"), getenv("IBM_CLOUD_API_KEY"));
    $certificateService = new CertificateService($httpClient, getenv("INTERNAL_IAM_BASE_URL"));
    $authenticationService = new AuthenticationService($distributedCacheClient, $httpClient, getenv("IAM_APP_CLIENT_ID"), "localhost", getenv("SERVICE_PORT")); 
?>