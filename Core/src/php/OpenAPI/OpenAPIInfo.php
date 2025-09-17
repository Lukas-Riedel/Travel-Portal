<?php
    namespace Core\OpenAPI;

    use OpenApi\Attributes as OA;

    #[OA\Info(
        title: "Core API",
        version: "1.0.0"
    )]
    #[OA\SecurityScheme(
        securityScheme: "bearerAuth",
        type: "http",
        scheme: "bearer",
        bearerFormat: "JWT"
    )]
    #[OA\Server(url: BASE_URL)]
    class OpenAPIInfo {
        // Intentionally empty.
    }
?>