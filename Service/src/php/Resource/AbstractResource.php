<?php
    namespace Service\Resource;

    use Service\Routing\AuthMiddleware;
    use Service\Routing\AuthorizationException;
    use Service\Service\Authentication\AccessToken;
    use Slim\Psr7\Request;

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

        public function validateArgumentKey(array $args, string $key) : mixed {
            if (!isset($args[$key])) {
                throw new \InvalidArgumentException("The required argument '$key' is missing.");
            }
            if (empty($args[$key])) {
                throw new \InvalidArgumentException("The required argument '$key' is empty.");
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
            if (empty($body[$field])) {
                throw new \InvalidArgumentException("The required request body field '$field' is empty.");
            }
            return $body[$field];
        }
    }
?>