<?php
    require_once(dirname(__FILE__) . "/../model/TargetError.php");

    abstract class Handler {
        abstract public function handle($input);
        abstract public function getRequiredRole();
        abstract public function isProtected();
        abstract public function getTag();
        abstract public function getPath();
        abstract public function getParameters();
        abstract public function getMethod();
        abstract public function getShortDescription();
        abstract public function getLongDescription();
        abstract public function getRequestExamples();
        abstract public function getResponseExamples();

        protected function createResponse($code, $body) {
            $code = $body instanceof TargetError ? $body->getCode() : $code;
            return array("code" => $code, "body" => $body);
        }

        protected function create404Response($resource, $id) {
            return $this->createResponse(404, array("code" => 404, "error" => "Requested resource was not found.", "details" => array("resource" => $resource, "id" => $id)));
        }

        protected function createRequestExample($name, $body) {
            return array("name" => $name, "body" => $body);
        }

        protected function createResponseExample($name, $code, $body) {
            return array("name" => $name, "body" => $this->createResponse($code, $body));
        }

        protected function create204ResponseExample() {
            return $this->createResponseExample("Item removed", 204, NULL);
        }

        protected function create400ResponseExample() {
            return $this->createResponseExample("Invalid category reference", 400, '{"code":400,"error":"InvalidArgumentException","message":"The included region \'Paradise\' does not exist.","details":{"endpoint":"/api/categories","arguments":{"name":"World","category":"CONTINENT","includedRegions":["Paradise"],"excludedRegions":[]},"trace":["#0 /data/web/virtuals/254146/virtual/www/domains/lriedel.cz/php/handler/CreateCategoryHandler.php(8): CategoryService->createCompositeRegion(\'World\', \'CONTINENT\', Array, Array)","#1 /data/web/virtuals/254146/virtual/www/domains/lriedel.cz/php/api.php(103): CreateCategoryHandler->handle(Array)","#2 {main}"],"ipAddress":"89.103.191.6"}}');
        }

        protected function create401ResponseExample() {
            return $this->createResponseExample("Missing access token", 401, '{"code":401,"error":"AuthenticationException","message":"The access token was not provided.","details":{"endpoint":"/api/categories","arguments":[],"trace":["#0 /data/web/virtuals/254146/virtual/www/domains/lriedel.cz/php/api.php(96): AuthenticationService->getAccessToken(NULL)","#1 {main}"],"ipAddress":"89.103.191.6"}}');
        }

        protected function create403ResponseExample() {
            return $this->createResponseExample("Insufficient permissions", 403, '{"code":403,"error":"AuthorizationException","message":"The user is not authorized to perform this action.","details":{"endpoint":"/api/jobs/run","arguments":{"action":"UpdateCalendar","args":{"watchId":"314f1767-a7e8-4e53-90a0-a392cc99eb5c"}},"trace":["#0 {main}"],"ipAddress":"89.103.191.6"}}');
        }

        protected function create404ResponseExample() {
            return $this->createResponseExample("Item not found", 404, '{"code":404,"error":"Requested resource was not found.","details":{"resource":"places","id":"100000"}}');
        }

        protected function createPathParameter($name, $type, $examples) {
            return $this->createParameter("path", TRUE, $name, $type, $examples);
        }

        protected function createQueryParameter($name, $type, $examples) {
            return $this->createParameter("query", FALSE, $name, $type, $examples);
        }

        private function createParameter($category, $required, $name, $type, $examples) {
            return array("category" => $category, "required" => $required, "name" => $name, "type" => $type, "examples" => $examples);
        }
    }
?>