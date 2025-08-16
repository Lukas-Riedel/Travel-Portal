<?php
    namespace Core\Routing;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;

    class PayloadDecodingMiddleware implements MiddlewareInterface {

        public const ENCODED_REQUEST_BODY_QUERY_PARAM = "encodedRequestBody";

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            $queryParams = $request->getQueryParams();
            if (isset($queryParams[self::ENCODED_REQUEST_BODY_QUERY_PARAM])) {
                $decoded = base64_decode($queryParams[self::ENCODED_REQUEST_BODY_QUERY_PARAM]);
                $request = $request->withParsedBody(json_decode($decoded, true));
            }

            return $handler->handle($request);
        }
    }
?>