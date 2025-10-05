<?php
    namespace Core\Routing;

    use Core\OpenLineage\OpenLineageEventManager;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;

    class OpenLineageMiddleware implements MiddlewareInterface {

        private readonly OpenLineageEventManager $openLineageEventManager;

        public function __construct(OpenLineageEventManager $openLineageEventManager) {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            $this->openLineageEventManager->initializeEvent(ltrim($request->getUri()->getPath(), "/"));
            $response = $handler->handle($request);
            $this->openLineageEventManager->publishCurrentEventAsync();
            return $response;
        }
    }
?>