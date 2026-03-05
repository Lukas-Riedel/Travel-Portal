<?php

    namespace Core\Service\Document;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Document",
        type: "object",
        description: "An object representing a document",
        required: ["id", "name", "code", "issuer"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the document",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "name",
                type: "string",
                description: "The name of the document",
                example: "EEA ID Card"
            ),
            new OA\Property(
                property: "code",
                type: "string",
                description: "The code of the document",
                example: "203432977"
            ),
            new OA\Property(
                property: "issuer",
                type: "string",
                description: "The issuer of the document",
                example: "Prague 4"
            ),
            new OA\Property(
                property: "expiration",
                type: "integer",
                description: "The expiration of the document in epoch seconds",
                example: 1753912800
            )
        ]
    )]
    class Document implements \JsonSerializable {
        
        private ?string $id;
        private readonly string $name;
        private readonly string $code;
        private readonly string $issuer;
        private readonly ?int $expiration;

        public function __construct(?string $id, string $name, string $code, string $issuer, ?int $expiration) {
            $this->id = $id;
            $this->name = $name;
            $this->code = $code;
            $this->issuer = $issuer;
            $this->expiration = $expiration;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCode() : string {
            return $this->code;
        }

        public function getIssuer() : string {
            return $this->issuer;
        }

        public function getExpiration() : ?int {
            return $this->expiration;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>