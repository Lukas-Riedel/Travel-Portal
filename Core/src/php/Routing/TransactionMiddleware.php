<?php
    namespace Core\Routing;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;
    use Core\Provider\DatabaseProvider;

    class TransactionMiddleware implements MiddlewareInterface {
        private \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            $this->databaseProvider->beginTransaction();
            try {
                $response = $handler->handle($request);
                $this->databaseProvider->commit();
                return $response;
            } catch (\Throwable $e) {
                $this->databaseProvider->rollback();
                throw $e;
            }
        }
    }
?>