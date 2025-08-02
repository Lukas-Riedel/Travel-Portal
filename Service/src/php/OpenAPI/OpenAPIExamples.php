<?php
    namespace Service\OpenAPI;

    use OpenApi\Attributes as OA;

    #[OA\Components(
        examples: [
            new OA\Examples(
                example: "Unauthorized",
                summary: "Unauthorized. The request required user authentication.",
                value: [
                    "code" => 401,
                    "type" => "AuthenticationException",
                    "message" => "The access token expired at 2025-08-02T18:08:35.269Z.",
                    "trace" => [],
                    "path" => "/configuration"
                ]
            ),
            new OA\Examples(
                example: "Forbidden",
                summary: "Forbidden. The user did not have access to the requested resource.",
                value: [
                    "code" => 403,
                    "type" => "AuthorizationException",
                    "message" => "You are not allowed to access this resource.",
                    "trace" => [],
                    "path" => "/inconsistencies"
                ]
            ),
            new OA\Examples(
                example: "BadRequest",
                summary: "Bad Request. The request has invalid syntax or cannot be fulfilled.",
                value: [
                    "code" => 400,
                    "type" => "RuntimeException",
                    "message" => "The configuration key is missing.",
                    "trace" => [],
                    "path" => "/trips/34e5658b-d0c8-47e0-8ec7-846f1e28835b/notes"
                ]
            ),
            new OA\Examples(
                example: "NotFound",
                summary: "Not Found. The requested resource does not exist.",
                value: [
                    "code" => 400,
                    "type" => "NotFoundException",
                    "message" => "The entity with the key 'eb332f42-d5de-43d3-a90b-6f1b37ead81e' could not be found.",
                    "trace" => [],
                    "path" => "/places/eb332f42-d5de-43d3-a90b-6f1b37ead81e"
                ]
            )
        ]
    )]
    class OpenAPIExamples {

    }
?>