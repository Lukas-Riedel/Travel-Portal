<?php
    class TargetError implements JsonSerializable {        
        private $code;
        private $error;
        private $action;
        private $arguments;
        private $trace;

        public function __construct($error, $action, $arguments, $includeTrace) {
            $this->code = TargetError::resolveErrorCode($error);
            $this->error = $error->getMessage();
            $this->action = $action;
            $this->arguments = $arguments;
            $this->trace = $includeTrace ? $error->getTraceAsString() : NULL;
        }

        public function getCode() {
            return $this->code;
        }

        public function getError() {
            return $this->error;
        }

        public function getAction() {
            return $this->action;
        }

        public function getArguments() {
            return $this->arguments;
        }

        public function getTrace() {
            return $this->trace;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            $arr = array(
                "code" => $this->code,
                "error" => $this->error,
                "details" => array(
                    "action" => $this->action,
                    "arguments" => $this->arguments));
            if ($this->trace !== NULL) {
                $arr["details"]["trace"] = $this->trace;
            }
            return $arr;
        }

        private static function resolveErrorCode($e) {
            if ($e instanceof ErrorException) {
                return 403;
            }
            // Return 400 for everything else for now.
            return 400;
        }
    }
?>