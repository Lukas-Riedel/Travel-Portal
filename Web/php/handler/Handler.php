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
        abstract public function getOperationId();
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
            return $this->createResponseExample("Invalid album", 400, '{"code":400,"error":"Invalid album ID.","details":{"action":"UpdateAlbum","arguments":{"placeId":"2507","albumId":"AGhjs2lxV1nXaHgWw_G6CAL9Sm-jnZX7wU5qM_ai2Ro6OzEv2olcSAwgaB_gmXUrep2CPl2ygyjxq"}}}');
        }

        protected function create403ResponseExample() {
            return $this->createResponseExample("Insufficient permissions", 403, '{"code":403,"error":"The action can only be executed by users with admin role.","details":{"action":"AddAlbum","arguments":{"placeId":2507,"date":1589580000}}}');
        }

        protected function create404ResponseExample() {
            return $this->createResponseExample("Item not found", 404, '{"code":404,"error":"Requested item was not found.","details":{"resource":"places","item":25070}}');
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