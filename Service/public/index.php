<?php
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Service\Routing\AuthMiddleware;
    use Slim\Factory\AppFactory;
    use Service\Routing\ErrorHandlingMiddleware;
    use Service\Routing\JsonInvocationStrategy;
    use Service\Routing\RequestError;
    use Service\Routing\TransactionMiddleware;
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
    $app->add(new ErrorHandlingMiddleware());

    (require_once(__DIR__ . "/src/php/routes.php"))($app);

    // TODO: Remove after rewriting all handlers, keep only the swagger fallback
    $app->any("/{path:.*}", function (ServerRequestInterface $request, ResponseInterface $response, array $path) use(&$basePath) {
        $_GET["path"] = "/" . ltrim($path["path"], "/");
        require __DIR__ . "/src/php/bootstrap.php";
        ob_start();
        include __DIR__ . "/api.php";
        $body = ob_get_clean();
        if (empty($body)) {
            $acceptHeader = $request->getHeaderLine("Accept");
            if (str_contains($acceptHeader, "text/html")) {
                return $response
                    ->withStatus(302)
                    ->withHeader("Location", $basePath. "/swagger");
            }

            $error = new RequestError(
                404, 
                "RouteNotFoundException", 
                "The route handler for the path '" . $request->getUri()->getPath() . "' does not exist.",
                array(), 
                $request->getUri()->getPath()
            );

            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }
        $legacyCode = http_response_code() ?: 200;
        $response->getBody()->write($body);
        return $response->withStatus($legacyCode)->withHeader("Content-Type", "application/json");
    })->setInvocationStrategy(new RequestResponse());

    $app->run();
?>