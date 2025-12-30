<?php
    use Common\Routing\AuthMiddleware;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Common\Routing\CorsMiddleware;
    use Slim\Factory\AppFactory;
    use Common\Routing\ErrorHandlingMiddleware;
    use Common\Routing\JsonInvocationStrategy;
    use Common\Routing\LoggingMiddleware;
    use Common\Routing\RequestError;
    use Ramsey\Uuid\Uuid;
    use Slim\Handlers\Strategies\RequestResponse;

    require_once(__DIR__ . "/../src/bootstrap.php");
    
    $transactionId = Uuid::uuid4()->toString();
    $logger->pushProcessor(function($record) use($transactionId) {
        $record["context"]["transaction_id"] = $transactionId;
        $record["extra"]["transaction_id"] = $transactionId;
        return $record;
    });

    $basePath = parse_url(getenv("IAM_BASE_URL"))["path"] ?? "";

    $app = AppFactory::create();
    $app->getRouteCollector()->setDefaultInvocationStrategy(new JsonInvocationStrategy());
    $app->setBasePath($basePath);

    $app->add(new AuthMiddleware($authenticationService, $basePath, array("/token", "/google/auth", "/management/liveness", "/management/readiness")));
    $app->addRoutingMiddleware();
    $app->add(new LoggingMiddleware($logger));
    $app->addBodyParsingMiddleware();
    $app->add(new ErrorHandlingMiddleware($logger));
    $app->add(new CorsMiddleware(explode(",", getenv("ALLOWED_REQUEST_ORIGINS"))));

    (require_once(__DIR__ . "/../src/routes.php"))($app, getenv("SERVICE_NAME"), getenv("VERSION_TAG"), 
        getenv("GOOGLE_API_CLIENT_ID"), getenv("IAM_BASE_URL"));

    $app->any("/{path:.*}", function(ServerRequestInterface $request, ResponseInterface $response, array $routeArguments) {
        $error = new RequestError(404, "RouteNotFoundException",
            "The resource '" . $request->getUri()->getPath() . "' does not exist.",
            array(), $request->getUri()->getPath());
        $response->getBody()->write(json_encode($error));

        return $response
            ->withStatus($error->getCode())
            ->withHeader("Content-Type", "application/json");
    })->setInvocationStrategy(new RequestResponse());

    $app->run();
?>