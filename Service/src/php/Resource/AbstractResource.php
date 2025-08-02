<?php
    namespace Service\Resource;

    use Service\Routing\AuthMiddleware;
    use Service\Routing\AuthorizationException;
    use Slim\Psr7\Request;

    abstract class AbstractResource {

        public function validateAdminPermissions(Request $request) : void {          
            $accessToken = $request->getAttribute(AuthMiddleware::ACCESS_TOKEN_ATTRIBUTE_KEY);  
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
    }
?>