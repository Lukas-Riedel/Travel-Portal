<?php
    namespace Common\Resource;

    use Common\Service\Authentication\UserInfo;
    use Common\CommonConstants;
    use Common\Routing\AuthorizationException;
    use Slim\Psr7\Request;

    // TODO: Find better names for methods in this class.
    abstract class AbstractResource {

        public function getUserInfo(Request $request) : UserInfo {
            return $request->getAttribute(CommonConstants::USER_INFO_ATTRIBUTE_KEY);
        }

        public function isAdmin(Request $request) : bool {
            $accessToken = $this->getUserInfo($request);
            return $accessToken->isAdmin();
        }

        public function validateAdminPermissions(Request $request) : void {          
            $userInfo = $this->getUserInfo($request);
            if (!$userInfo->isAdmin()) {
                throw new AuthorizationException($userInfo);
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
            return $request->getQueryParams()[$key] ?? null;
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
            if ($parsedBody === null) {
                throw new \InvalidArgumentException("The request body must be a valid JSON object.");
            }
            return $parsedBody;
        }

        public function validateJsonBodyField(Request $request, string $field) : mixed {
            $body = $this->validateJsonBody($request);
            if (!isset($body[$field])) {
                throw new \InvalidArgumentException("The required request body field '$field' is missing.");
            }
            if ($body[$field] === null || $body[$field] === "" || $body[$field] === array()) {
                throw new \InvalidArgumentException("The required request body field '$field' IS null or empty.");
            }
            return $body[$field];
        }
        
        public function validateJsonBodyNullableField(Request $request, string $field) : mixed {
            return $this->validateJsonBody($request)[$field] ?? null;
        }

        public function validateJsonBodyFieldExistence(Request $request, string $field) : bool {
            $body = $this->validateJsonBody($request);
            return array_key_exists($field, $body);
        }
    }
?>