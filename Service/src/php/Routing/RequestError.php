<?php
    namespace Service\Routing;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "RequestError",
        type: "object",
        description: "A class representing a request error",
        required: ["code", "type", "message", "trace", "path"],
        properties: [
            new OA\Property(
                property: "code",
                type: "integer",
                description: "HTTP status code",
                example: 401
            ),
            new OA\Property(
                property: "type",
                type: "string",
                description: "Error type identifier",
                example: "AuthenticationException"
            ),
            new OA\Property(
                property: "message",
                type: "string",
                description: "Human readable error message",
                example: "The access token expired at 2025-08-02T18:08:35.269Z."
            ),
            new OA\Property(
                property: "trace",
                type: "array",
                description: "Stack trace or debugging info",
                items: new OA\Items(type: "string"),
                example: []
            ),
            new OA\Property(
                property: "path",
                type: "string",
                description: "Path where the error occurred",
                example: "/configuration"
            ),
        ]
    )]
    class RequestError implements \JsonSerializable {
        private readonly int $code;
        private readonly string $type;
        private readonly string $message;
        private readonly array $trace;
        private readonly string $path;

        public function __construct(int $code, string $type, string $message, array $trace, string $path) {
            $this->code = $code;
            $this->type = $type;
            $this->message = $message;
            $this->trace = $trace;
            $this->path = $path;
        }

        public function getCode() : int {
            return $this->code;
        }

        public function getType() : string {
            return $this->type;
        }

        public function getMessage() : string {
            return $this->message;
        }

        public function getTrace() : array {
            return $this->trace;
        }

        public function getPath() : string {
            return $this->path;
        }
        

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>