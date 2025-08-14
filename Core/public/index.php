<?php
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Core\Routing\AuthMiddleware;
    use Slim\Factory\AppFactory;
    use Core\Routing\ErrorHandlingMiddleware;
    use Core\Routing\JsonInvocationStrategy;
    use Core\Routing\LoggingMiddleware;
    use Core\Routing\RequestError;
    use Core\Routing\TransactionMiddleware;
    use Slim\Handlers\Strategies\RequestResponse;

    require_once(__DIR__ . "/src/php/bootstrap.php");

    $basePath = parse_url(BASE_URL)["path"] ?? "";

    $app = AppFactory::create();
    $app->getRouteCollector()->setDefaultInvocationStrategy(new JsonInvocationStrategy());
    $app->setBasePath($basePath);

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->add(new TransactionMiddleware($databaseProvider));
    $app->add(new AuthMiddleware($authenticationService, $basePath));
    $app->add(new ErrorHandlingMiddleware($logger));
    $app->add(new LoggingMiddleware($logger));

    (require_once(__DIR__ . "/src/php/routes.php"))($app);

    $app->any("/{path:.*}", function (ServerRequestInterface $request, ResponseInterface $response, array $routeArguments) use(&$basePath) {
        // TODO: Remove after rewriting all handlers, keep only the swagger fallback
        $_GET["path"] = "/" . ltrim($routeArguments["path"], "/");
        require __DIR__ . "/src/php/bootstrap.php";
        ob_start();
        include __DIR__ . "/api.php";
        $body = ob_get_clean();
        if (!empty($body)) {
            $legacyCode = http_response_code() ?: 200;
            $response->getBody()->write($body);
            return $response->withStatus($legacyCode)->withHeader("Content-Type", "application/json");
        }

        $acceptHeader = $request->getHeaderLine("Accept");
        if (str_contains($acceptHeader, "text/html")) {
            return $response
                ->withStatus(302)
                ->withHeader("Location", $basePath. "/swagger");
        }

        $error = new RequestError(404, "RouteNotFoundException",
            "The route handler for the path '" . $request->getUri()->getPath() . "' does not exist.",
            array(), $request->getUri()->getPath());

        $response->getBody()->write(json_encode($error));
        return $response
            ->withStatus(404)
            ->withHeader("Content-Type", "application/json");
    })->setInvocationStrategy(new RequestResponse());

    $app->run();
?>