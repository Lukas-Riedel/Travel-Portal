<?php
    class GetSwaggerHandler extends Handler {
        public function handle($input) {            
            $methodsOrder = array(
                "post" => 0,
                "get" => 1,
                "patch" => 2,
                "put" => 3,
                "delete" => 4);
                
            $handlers = array();
            foreach (array_diff(scandir(__DIR__), array('.', '..', 'Handler.php', 'GetSwaggerHandler.php')) as &$handlerFileName) {
                require_once(__DIR__ . "/" . $handlerFileName);
                $handlerFileNameTokens = explode(".", $handlerFileName);
                $handlers[] = new $handlerFileNameTokens[0];
            }

            $tags = array_unique(array_map(function($handler) { return $handler->getTag(); }, $handlers));

            $paths = array();
            foreach ($handlers as &$handler) {
                if (!array_key_exists($handler->getPath(), $paths)) {
                    $paths[$handler->getPath()] = array();
                }

                $paths[$handler->getPath()][strtolower($handler->getMethod())] = $this->getApiEndpoint($handler);
                uksort($paths[$handler->getPath()], function($a, $b) use(&$methodsOrder) { return $methodsOrder[$a] - $methodsOrder[$b]; });
            }
            ksort($paths);

            return $this->createResponse(200, array(
                "openapi" => "3.0.1",
                "info" => array("title" => "Travel Portal API", "version" => "1.0.0"),
                "tags" => array_map(function($tag) { return array("name" => $tag); }, $tags),
                "servers" => array(array("url" => BASE_URL)),
                "paths" => $paths,
                "security" => array(
                    array(
                        "bearerAuth" => array())),
                "components" => array(
                    "securitySchemes" => array(
                        "bearerAuth" => array(
                            "type" => "http", 
                            "scheme" => "bearer", 
                            "bearerFormat" => "JWT")))));
        }

        public function getRequiredRole() {
            return null;
        }
        
        public function isProtected() {
            return false;
        }

        public function getTag() {
            return "Swagger";
        }

        public function getPath() {
            return "/swagger_old";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a Swagger definition of the API";
        }
        
        public function getLongDescription() {
            return "Retrieves a Swagger definition of the API.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array();
        }

        private function getApiEndpoint($handler) {
            $endpoint = array(
                "summary" => $handler->getShortDescription(),
                "description" => $handler->getLongDescription(),
                "operationId" => str_replace("Handler", "", get_class($handler)),
                "tags" => array($handler->getTag()));
    
            $parameters = $handler->getParameters();
            if (count($parameters) > 0) {
                foreach ($parameters as &$parameter) {
                    $convertedParameter = array("in" => $parameter["category"], "required" => $parameter["required"], "name" => $parameter["name"], "schema" => array("type" => $parameter["type"]));
                    if (is_array($parameter["examples"])) {
                        $convertedParameter["schema"]["enum"] = $parameter["examples"];
                    }
                    else {
                        $convertedParameter["schema"]["example"] = $parameter["examples"];
                    }
                    $endpoint["parameters"][] = $convertedParameter;
                }
            }
    
            $requestExamples = $handler->getRequestExamples();
            if (count($requestExamples) > 0) {
                $convertedExamples = array();
                foreach ($requestExamples as &$requestExample) {
                    $convertedExamples[strtolower(str_replace(" ", "", $requestExample["name"]))] = array("summary" => $requestExample["name"], "value" => json_decode($requestExample["body"], true));
                }
                $endpoint["requestBody"] = array("content" => array("application/json" => array("examples" => $convertedExamples)));
            }
    
            $responseExamples = $handler->getResponseExamples();
            if (count($responseExamples) > 0) {
                $convertedExamples = array();
                foreach ($responseExamples as &$responseExample) {
                    $body = $responseExample["body"]["body"];
                    if (!array_key_exists($responseExample["body"]["code"], $convertedExamples)) {
                        $convertedExamples[$responseExample["body"]["code"]] = array();
                    }
                    if ($body != null) {
                        $convertedExamples[$responseExample["body"]["code"]]["content"]["application/json"]["examples"][strtolower(str_replace(" ", "", $responseExample["name"]))] = array("summary" => $responseExample["name"], "value" => json_decode($body, true));
                    }
                }
                $endpoint["responses"] = $convertedExamples;
            }
    
            return $endpoint;
        }
    }
?>