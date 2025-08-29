<?php
    namespace Core\Routing;

    use Core\Common\CommonConstants;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Message\ResponseInterface;

    class PayloadDecodingMiddleware implements MiddlewareInterface {

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
            $queryParams = $request->getQueryParams();
            if (isset($queryParams[CommonConstants::ENCODED_REQUEST_BODY_QUERY_PARAMETER_KEY])) {
                $decoded = base64_decode($queryParams[CommonConstants::ENCODED_REQUEST_BODY_QUERY_PARAMETER_KEY]);
                $request = $request->withParsedBody(json_decode($decoded, true));
            }

            return $handler->handle($request);
        }
    }
?>