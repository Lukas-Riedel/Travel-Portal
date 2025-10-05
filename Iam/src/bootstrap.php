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
    use function Secrets\getenv;
    
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
    $encryptionClient = new EncryptionClient();
    
    // Authentication service.
    $authenticationService = new AuthenticationService();

    // Services.
    $tokenService = new TokenService($httpClient);
    $userService = new UserService($tokenService, $httpClient);
    $googleService = new GoogleService($userService, $httpClient, $encryptionClient);
    $ibmCloudService = new IbmCloudService($httpClient);
?>