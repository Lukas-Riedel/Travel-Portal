<?php
    namespace Core\Resource;

    use Core\Routing\AuthMiddleware;
    use Core\Routing\AuthorizationException;
    use Core\Service\Authentication\AccessToken;
    use Slim\Psr7\Request;

    // TODO: Find better names for methods in this class.
    abstract class AbstractResource {

        public function getAccessToken(Request $request) : AccessToken {
            return $request->getAttribute(AuthMiddleware::ACCESS_TOKEN_ATTRIBUTE_KEY);
        }

        public function validateAdminPermissions(Request $request) : void {          
            $accessToken = $this->getAccessToken($request);
            if (!$accessToken->isAdmin()) {
                throw new AuthorizationException($accessToken);
            }
        }

        public function validateQueryParameter(Request $request, string $key) : mixed {
            $args = $request->getQueryParams();
            if (!isset($args[$key])) {
                throw new \InvalidArgumentException("The required query parameter '$key' is missing.");
            }
            if (empty($args[$key])) {
                throw new \InvalidArgumentException("The required query parameter '$key' is empty.");
            }
            return $args[$key];
        }

        public function validateQueryNullableParameter(Request $request, string $key) : ?string {
            $queryParams = $request->getQueryParams();
            return isset($queryParams[$key]) ? $queryParams[$key] : NULL;
        }

        public function validatePathArgument(array $args, string $key) : mixed {
            if (!isset($args[$key])) {
                throw new \InvalidArgumentException("The required path argument '$key' is missing.");
            }
            if (empty($args[$key])) {
                throw new \InvalidArgumentException("The required path argument '$key' is empty.");
            }
            return $args[$key];
        }

        public function validateJsonBody(Request $request) : mixed {
            $parsedBody = $request->getParsedBody();
            if ($parsedBody === NULL) {
                throw new \InvalidArgumentException("The request body must be a valid JSON object.");
            }
            return $parsedBody;
        }

        public function validateJsonBodyField(Request $request, string $field) : mixed {
            $body = $this->validateJsonBody($request);
            if (!isset($body[$field])) {
                throw new \InvalidArgumentException("The required request body field '$field' is missing.");
            }
            if ($body[$field] === NULL || $body[$field] === "" || $body[$field] === array()) {
                throw new \InvalidArgumentException("The required request body field '$field' is null or empty.");
            }
            return $body[$field];
        }
        
        public function validateJsonBodyNullableField(Request $request, string $field) : mixed {
            $body = $this->validateJsonBody($request);
            return isset($body[$field]) ? $body[$field] : NULL;
        }
    }
?>