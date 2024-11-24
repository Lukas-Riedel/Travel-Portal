<?php
    class TargetError implements JsonSerializable {        
        private $code;
        private $error;
        private $message;
        private $trace;
        private $arguments;

        public function __construct($code, $exception, $arguments) {
            $this->code = $code;
            $this->error = get_class($exception);
            $this->message = $exception->getMessage();
            $this->trace = explode("\n", $exception->getTraceAsString());
            $this->arguments = $arguments;
        }

        public function getCode() : int {
            return $this->code;
        }

        public function getError() : string {
            return $this->error;
        }

        public function getMessage() : string {
            return $this->message;
        }

        public function getTrace() : array {
            return $this->trace;
        }

        public function getArguments() : array {
            return $this->arguments;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return array(
                "code" => $this->code,
                "error" => $this->error,
                "message" => $this->message,
                "details" => array(
                    "endpoint" => parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH),
                    "arguments" => $this->arguments,
                    "trace" => $this->trace,
                    "ipAddress" => $_SERVER["REMOTE_ADDR"]));
        }
    }
?>