<?php
    require_once(__DIR__ . "/../vendor/autoload.php");
    require_once(__DIR__ . "/../../secrets.php");

    use Common\Service\Authentication\AuthenticationService;
    use Common\Client\Http\HttpClient;
    use Iam\Client\Encryption\EncryptionClient;
    use Iam\Service\Google\GoogleService;
    use Iam\Service\IbmCloud\IbmCloudService;
    use Iam\Service\Token\TokenService;
    use Iam\Service\User\UserService;
    use Itspire\MonologLoki\Handler\LokiHandler;
    use Monolog\Handler\WhatFailureGroupHandler;
    use Monolog\Logger;
    use function Secrets\getenv; // TODO: Delete when switching to k8s.
    
    $onError = function($level, $message, $file, $line) {
        throw new \ErrorException($message);
    };
    set_error_handler($onError);

    $transactionId = uniqid();

    // Logger.
    $logger = new Logger("iam");
    $handler = new WhatFailureGroupHandler(array(
        new LokiHandler(array(
            "entrypoint" => getenv("GRAFANA_LOKI_ENTRYPOINT"),
            "context" => array(
                "transactionId" => $transactionId
            ),
            "labels" => array(
                "service" => "iam",
                "transactionId" => $transactionId
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
    $httpClient = new HttpClient($logger);
    $encryptionClient = new EncryptionClient(getenv("ENCRYPTION_PRIVATE_KEY"));
    
    // Authentication service.
    $authenticationService = new AuthenticationService(getenv("IAM_APP_CLIENT_ID"), getenv("JWKS_PUBLIC_KEY"));

    // Services.
    $tokenService = new TokenService($httpClient, getenv("IAM_APP_CLIENT_ID"), getenv("INTERNAL_IAM_BASE_URL"));
    $userService = new UserService($tokenService, $httpClient, getenv("IAM_APP_CLIENT_ID"), getenv("IAM_BACKEND_CLIENT_ID"), getenv("IAM_BACKEND_CLIENT_SECRET"), getenv("INTERNAL_ADMIN_IAM_BASE_URL"));
    $googleService = new GoogleService($userService, $httpClient, $encryptionClient, getenv("FCM_PROJECT_ID"), getenv("FCM_PRIVATE_KEY_ID"), getenv("FCM_PRIVATE_KEY"), getenv("FCM_CLIENT_EMAIL"),
        getenv("FCM_CLIENT_ID"), getenv("GOOGLE_API_CLIENT_ID"), getenv("FCM_CLIENT_X509_CERTIFICATE_URL"), getenv("GOOGLE_API_CLIENT_SECRET"), getenv("IAM_BASE_URL"));
    $ibmCloudService = new IbmCloudService($httpClient, getenv("IBM_CLOUD_API_KEY"));
?>