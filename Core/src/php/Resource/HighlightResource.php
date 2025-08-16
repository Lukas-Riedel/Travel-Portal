<?php
    namespace Core\Resource;

    use Core\Routing\NotFoundException;
    use Core\Routing\NotUpdatedException;
    use Core\Service\Highlight\HighlightService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Highlights")]
    class HighlightResource extends AbstractResource {

        private readonly HighlightService $highlightService;

        public function __construct(HighlightService $highlightService) {
            $this->highlightService = $highlightService;
        }

        public static function register(App $app, HighlightService $highlightService) : void {
            $resource = new self($highlightService);

            $app->group("/highlights", function($group) use($resource) {
                $group->get("/{highlightId}", [$resource, "getHighlight"]);
                $group->patch("/{highlightId}", [$resource, "updateHighlight"]);
            });
        }

        #[OA\Get(
            path: "/highlights/{highlightId}",
            summary: "Retrieve a highlight with the specified identifier",
            operationId: "getHighlight",
            tags: ["Highlights"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "highlightId",
                    in: "path",
                    required: true,
                    description: "The identifier of the highlight",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a highlight with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Highlight")
                ),
                new OA\Response(
                    response: 400,
                    description: "Bad Request. The request had invalid syntax or could not be fulfilled.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Bad Request",
                                ref: "#/components/examples/BadRequest"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Unauthorized. The request required user authentication.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Unauthorized",
                                ref: "#/components/examples/Unauthorized"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 403,
                    description: "Forbidden. The user did not have access to the requested resource.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Forbidden",
                                ref: "#/components/examples/Forbidden"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 404,
                    description: "Not Found. The requested resource did not exist.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Not Found",
                                ref: "#/components/examples/NotFound"
                            )
                        ]
                    )
                )
            ]
        )]
        public function getHighlight(Request $request, Response $response, array $routeArguments) : mixed {    
            $highlightId = $this->validatePathArgument($routeArguments, "highlightId");
            
            $highlight = $this->highlightService->getHighlight($highlightId);
            if ($highlight === null) {
                throw new NotFoundException($highlightId);
            }

            return $highlight;
        }

        #[OA\Patch(
            path: "/highlights/{highlightId}",
            summary: "Update a highlight with the specified identifier",
            operationId: "updateHighlight",
            tags: ["Highlights"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "attributes",
                            description: "The highlight quality attributes",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "composition",
                                    description: "The composition score of the highlight",
                                    type: "integer",
                                    format: "int32",
                                    example: 100
                                ),
                                new OA\Property(
                                    property: "sky",
                                    description: "The sky score of the highlight",
                                    type: "integer",
                                    format: "int32",
                                    example: 90
                                ),
                                new OA\Property(
                                    property: "shadows",
                                    description: "The shadows score of the highlight",
                                    type: "integer",
                                    format: "int32",
                                    example: 70
                                ),
                                new OA\Property(
                                    property: "circumstances",
                                    description: "The circumstances score of the highlight",
                                    type: "integer",
                                    format: "int32",
                                    example: 100
                                ),
                                new OA\Property(
                                    property: "atmosphere",
                                    description: "The atmosphere score of the highlight",
                                    type: "integer",
                                    format: "int32",
                                    example: 90
                                )
                            ]
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "highlightId",
                    in: "path",
                    required: true,
                    description: "The identifier of the highlight",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a highlight with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Highlight")
                ),
                new OA\Response(
                    response: 400,
                    description: "Bad Request. The request had invalid syntax or could not be fulfilled.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Bad Request",
                                ref: "#/components/examples/BadRequest"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Unauthorized. The request required user authentication.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Unauthorized",
                                ref: "#/components/examples/Unauthorized"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 403,
                    description: "Forbidden. The user did not have access to the requested resource.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Forbidden",
                                ref: "#/components/examples/Forbidden"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 404,
                    description: "Not Found. The requested resource did not exist.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Not Found",
                                ref: "#/components/examples/NotFound"
                            )
                        ]
                    )
                )
            ]
        )]
        public function updateHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $highlightId = $this->validatePathArgument($routeArguments, "highlightId");
            
            $newAttributes = $this->validateJsonBodyNullableField($request, "attributes");
            if ($newAttributes !== null) {
                if (isset($newAttributes["composition"])) {
                    $wasUpdated = $this->highlightService->updateHighlightComposition($highlightId, $newAttributes["composition"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($highlightId);
                    }
                }

                if (isset($newAttributes["sky"])) {
                    $wasUpdated = $this->highlightService->updateHighlightSky($highlightId, $newAttributes["sky"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($highlightId);
                    }
                }

                if (isset($newAttributes["shadows"])) {
                    $wasUpdated = $this->highlightService->updateHighlightShadows($highlightId, $newAttributes["shadows"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($highlightId);
                    }
                }

                if (isset($newAttributes["circumstances"])) {
                    $wasUpdated = $this->highlightService->updateHighlightCircumstances($highlightId, $newAttributes["circumstances"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($highlightId);
                    }
                }

                if (isset($newAttributes["atmosphere"])) {
                    $wasUpdated = $this->highlightService->updateHighlightAtmosphere($highlightId, $newAttributes["atmosphere"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($highlightId);
                    }
                }
            }

            $highlight = $this->highlightService->getHighlight($highlightId);
            if ($highlight === null) {
                throw new NotFoundException($highlightId);
            }

            return $highlight;
        }
    }
?>