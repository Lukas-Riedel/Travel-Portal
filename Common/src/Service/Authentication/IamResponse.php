<?php
    namespace Common\Service\Authentication;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "IamResponse",
        type: "object",
        description: "A class representing an IAM response",
        required: ["accessToken", "expiresIn"],
        properties: [
            new OA\Property(
                property: "accessToken",
                type: "string",
                description: "The access token",
                example: "eyJhbGciOiJIUzI1NiJ9.e30.OPg7Wd7b43t_Zc3oX4-6s5KKT-y86K_O3mR3zU_VzX8"
            ),
            new OA\Property(
                property: "expiresIn",
                type: "integer",
                description: "The expiration time of the access token in seconds",
                example: 3600
            ),
            new OA\Property(
                property: "refreshToken",
                type: "string",
                description: "The refresh token",
                example: "eyJhbGciOiJIUzI1NiJ9.e30.OPg7Wd7b43t_Zc3oX4-6s5KKT-y86K_O3mR3zU_VzX8"
            ),
            new OA\Property(
                property: "refreshExpiresIn",
                type: "integer",
                description: "The expiration time of the access token in seconds",
                example: 86400
            )
        ]
    )]
    class IamResponse implements \JsonSerializable {     
        
        private readonly string $accessToken;
        private readonly int $expiresIn;
        private readonly ?string $refreshToken;
        private readonly ?int $refreshExpiresIn;

        public function __construct(string $accessToken, int $expiresIn, ?string $refreshToken, ?int $refreshExpiresIn) {
            $this->accessToken = $accessToken;
            $this->expiresIn = $expiresIn;
            $this->refreshToken = $refreshToken;
            $this->refreshExpiresIn = $refreshExpiresIn;
        }

        public function getAccessToken() : string {
            return $this->accessToken;
        }

        public function getExpiresIn() : int {
            return $this->expiresIn;
        }

        public function getRefreshToken() : ?string {
            return $this->refreshToken;
        }

        public function getRefreshExpiresIn() : ?int {
            return $this->refreshExpiresIn;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>