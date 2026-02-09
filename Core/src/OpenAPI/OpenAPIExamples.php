<?php
    namespace Core\OpenAPI;

    use OpenApi\Attributes as OA;

    #[OA\Components(
        examples: [
            new OA\Examples(
                example: "Unauthorized",
                summary: "Unauthorized",
                value: [
                    "code" => 401,
                    "type" => "AuthenticationException",
                    "message" => "The access token expired at 2025-08-02T18:08:35.269Z.",
                    "path" => "/configuration"
                ]
            ),
            new OA\Examples(
                example: "Forbidden",
                summary: "Forbidden",
                value: [
                    "code" => 403,
                    "type" => "AuthorizationException",
                    "message" => "The user '89754ffa-c9bb-478c-afbd-8122742815f7' is not authorized to access this resource.",
                    "path" => "/inconsistencies"
                ]
            ),
            new OA\Examples(
                example: "BadRequest",
                summary: "Bad Request",
                value: [
                    "code" => 400,
                    "type" => "RuntimeException",
                    "message" => "The configuration key is missing.",
                    "path" => "/trips/34e5658b-d0c8-47e0-8ec7-846f1e28835b/notes"
                ]
            ),
            new OA\Examples(
                example: "NotFound",
                summary: "Not Found",
                value: [
                    "code" => 400,
                    "type" => "NotFoundException",
                    "message" => "The entity with the key 'eb332f42-d5de-43d3-a90b-6f1b37ead81e' could not be found.",
                    "path" => "/places/eb332f42-d5de-43d3-a90b-6f1b37ead81e"
                ]
            )
        ]
    )]
    class OpenAPIExamples {
        // Intentionally empty.
    }
?>