<?php
    namespace Common\Service\Authentication;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "UserInfo",
        type: "object",
        description: "A class representing information about a user",
        required: ["userId", "roles"],
        properties: [
            new OA\Property(
                property: "userId",
                type: "string",
                description: "The identifier of the user",
                example: "ebd5572e-9d35-4ffe-8469-e436c6fba72f"
            ),
            new OA\Property(
                property: "client",
                type: "string",
                description: "The client of the user",
                example: "travel-portal-app"
            ),
            new OA\Property(
                property: "roles",
                type: "array",
                description: "The roles of the user",
                items: new OA\Items(type: "string"),
                example: []
            ),
        ]
    )]
    class UserInfo implements \JsonSerializable {        
        private readonly string $userId;
        private readonly string $client;
        private readonly array $roles;

        public function __construct(string $userId, string $client, array $roles) {
            $this->userId = $userId;
            $this->client = $client;
            $this->roles = $roles;
        }

        public function getUserId() : string {
            return $this->userId;
        }

        public function getClient() : string {
            return $this->client;
        }

        public function getRoles() : array {
            return $this->roles;
        }

        public function isAdmin() : bool {
            return in_array("ADMIN", $this->roles);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>