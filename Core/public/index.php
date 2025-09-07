<?php
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Core\Routing\AuthMiddleware;
    use Core\Routing\CorsMiddleware;
    use Slim\Factory\AppFactory;
    use Core\Routing\ErrorHandlingMiddleware;
    use Core\Routing\JsonInvocationStrategy;
    use Core\Routing\LoggingMiddleware;
    use Core\Routing\PayloadDecodingMiddleware;
    use Core\Routing\RequestError;
    use Core\Routing\TransactionMiddleware;
    use Slim\Handlers\Strategies\RequestResponse;

    require_once(__DIR__ . "/src/php/bootstrap.php");

    $basePath = parse_url(BASE_URL)["path"] ?? "";

    $app = AppFactory::create();
    $app->getRouteCollector()->setDefaultInvocationStrategy(new JsonInvocationStrategy());
    $app->setBasePath($basePath);

    $app->add(new TransactionMiddleware($databaseProvider));
    $app->add(new AuthMiddleware($authenticationService, $basePath));
    $app->addRoutingMiddleware();
    $app->add(new PayloadDecodingMiddleware());
    $app->addBodyParsingMiddleware();
    $app->add(new ErrorHandlingMiddleware($logger));
    $app->add(new LoggingMiddleware($logger));
    $app->add(new CorsMiddleware(explode(",", ALLOWED_REQUEST_ORIGINS)));

    (require_once(__DIR__ . "/src/php/routes.php"))($app);

    $app->any("/{path:.*}", function (ServerRequestInterface $request, ResponseInterface $response, array $routeArguments) use(&$basePath) {
        $acceptHeader = $request->getHeaderLine("Accept");
        if (str_contains($acceptHeader, "text/html")) {
            return $response
                ->withStatus(302)
                ->withHeader("Location", $basePath. "/swagger");
        }

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