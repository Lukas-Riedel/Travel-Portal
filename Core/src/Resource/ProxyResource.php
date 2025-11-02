<?php
    namespace Core\Resource;

    use Common\Client\Http\HttpMethod;
    use Common\Resource\AbstractResource;
    use Core\Client\Http\HttpClient;
    use Core\Common\CommonConstants;
    use Slim\App;
    use Slim\Handlers\Strategies\RequestResponse;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    class ProxyResource extends AbstractResource {

        private readonly string $coreBaseUrl;

        private readonly HttpClient $httpClient;

        public function __construct(string $coreBaseUrl, HttpClient $httpClient) {
            $this->coreBaseUrl = $coreBaseUrl;
            $this->httpClient = $httpClient;
        }

        public static function register(App $app, string $coreBaseUrl, HttpClient $httpClient) : void {
            $resource = new self($coreBaseUrl, $httpClient);

            $app->get(CommonConstants::GOOGLE_USER_CONTENT_PROXY_BASE_URL . "/{path:.*}", [$resource, "getGoogleUserContent"])->setInvocationStrategy(new RequestResponse());
        }

        public function getGoogleUserContent(Request $request, Response $response, array $routeArguments) : mixed {
            $proxiedUrl = str_replace($this->coreBaseUrl . CommonConstants::GOOGLE_USER_CONTENT_PROXY_BASE_URL, CommonConstants::GOOGLE_USER_CONTENT_BASE_URL, (string)$request->getUri());
            $response->getBody()->write($this->httpClient->executeRequest(HttpMethod::GET, $proxiedUrl));
            return $response
                ->withStatus(200)
                // TODO: Propagate content type from the HTTP response.
                ->withHeader("Content-Type", "image/jpeg");
        }  
    }
?>