<?php
    namespace Common\Resource;

    use Common\CommonConstants;
    use Common\Routing\AuthorizationException;
    use Common\Service\Authentication\UserInfo;
    use Common\Service\Authentication\UserRole;
    use Slim\Psr7\Request;
    
    abstract class AbstractResource {

        public function getAccessToken(Request $request) : ?string {
            return $request->getAttribute(CommonConstants::ACCESS_TOKEN_ATTRIBUTE_KEY);
        }

        public function getUserInfo(Request $request) : UserInfo {
            return $request->getAttribute(CommonConstants::USER_INFO_ATTRIBUTE_KEY);
        }
        
        public function getUserRoles(Request $request) : array {
            return $request->getAttribute(CommonConstants::USER_ROLES_ATTRIBUTE_KEY) ?? array();
        }

        public function isAllowlistedServiceAccount(Request $request) : bool {
            return $this->getUserInfo($request)->isAllowlistedServiceAccount();
        }

        public function isAgentServiceAccount(Request $request) : bool {
            $userInfo = $this->getUserInfo($request);
            // TODO: Propagate AGENT_CLIENT_ID to Request/UserInfo and use here.
            return $userInfo->getClient() === getenv("AGENT_CLIENT_ID");
        }

        public function hasRole(Request $request, UserRole $requiredRole) : bool {
            $roles = $this->getUserRoles($request);

            if ($this->isAllowlistedServiceAccount($request) && !$this->getUserInfo($request)->isImpersonated()) {
                return empty($roles) || $this->hasImpliedRoles($roles, $requiredRole);
            }

            return $this->hasImpliedRoles($roles, $requiredRole);
        }

        public function requireAllowlistedServiceAccount(Request $request) : void {
            if (!$this->isAllowlistedServiceAccount($request)) {
                throw new AuthorizationException($this->getUserInfo($request));
            }
        }

        public function requireRole(Request $request, UserRole $requiredRole) : void {
            if (!$this->hasRole($request, $requiredRole)) {
                throw new AuthorizationException($this->getUserInfo($request));
            }
        }

        public function requireQueryParameter(Request $request, string $key) : mixed {
            $args = $request->getQueryParams();
            if (!isset($args[$key])) {
                throw new \InvalidArgumentException("The required query parameter '$key' is missing.");
            }
            if (empty($args[$key])) {
                throw new \InvalidArgumentException("The required query parameter '$key' is empty.");
            }
            return $args[$key];
        }

        public function getQueryParameter(Request $request, string $key) : ?string {
            return $request->getQueryParams()[$key] ?? null;
        }

        public function requirePathArgument(array $args, string $key) : mixed {
            if (!isset($args[$key])) {
                throw new \InvalidArgumentException("The required path argument '$key' is missing.");
            }
            if (empty($args[$key])) {
                throw new \InvalidArgumentException("The required path argument '$key' is empty.");
            }
            return $args[$key];
        }

        public function requireJsonBody(Request $request) : mixed {
            $parsedBody = $request->getParsedBody();
            if ($parsedBody === null) {
                throw new \InvalidArgumentException("The request body must be a valid JSON object.");
            }
            return $parsedBody;
        }

        public function requireJsonBodyField(Request $request, string $field) : mixed {
            $body = $this->requireJsonBody($request);
            if (!isset($body[$field])) {
                throw new \InvalidArgumentException("The required request body field '$field' is missing.");
            }
            if ($body[$field] === null || $body[$field] === "" || $body[$field] === array()) {
                throw new \InvalidArgumentException("The required request body field '$field' is null or empty.");
            }
            return $body[$field];
        }
        
        public function getJsonBodyField(Request $request, string $field) : mixed {
            return $this->requireJsonBody($request)[$field] ?? null;
        }

        public function existsJsonBodyField(Request $request, string $field) : bool {
            $body = $this->requireJsonBody($request);
            return array_key_exists($field, $body);
        }

        private function hasImpliedRoles(array $roles, UserRole $requiredRole) : bool {
            foreach ($roles as $assignedRoleValue) {
                $assignedRole = UserRole::tryFrom($assignedRoleValue);
                if ($assignedRole && $assignedRole->implies($requiredRole)) {
                    return true;
                }
            }

            return false;
        }
    }
?>