<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Label\Label;
    use Core\Service\Label\LabelIncludedEntity;
    use Core\Service\Label\LabelService;
    use Monolog\Logger;
    use OpenApi\Attributes as OA;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Labels")]
    class LabelResource extends AbstractResource {

        private readonly LabelService $labelService;
        private readonly HighlightService $highlightService;
        private readonly Logger $logger;

        public function __construct(LabelService $labelService, HighlightService $highlightService, Logger $logger) {
            $this->labelService = $labelService;
            $this->highlightService = $highlightService;
            $this->logger = $logger;
        }

        public static function register(App $app, LabelService $labelService, HighlightService $highlightService, Logger $logger) : void {
            $resource = new self($labelService, $highlightService, $logger);

            $app->group("/labels", function($group) use($resource) {
                $group->get("", [$resource, "listLabels"]);
                $group->get("/{labelId}", [$resource, "getLabel"]);
                $group->patch("/{labelId}", [$resource, "updateLabel"]);
                $group->delete("/{labelId}", [$resource, "removeLabel"]);
                $group->post("/{labelId}/highlights", [$resource, "createLabelHighlight"]);
                $group->post("/{labelId}/highlights/refresh", [$resource, "refreshLabelHighlights"]);
                $group->delete("/{labelId}/highlights/{highlightId}", [$resource, "removeLabelHighlight"]);
            });
        }

        #[OA\Get(
            path: "/labels",
            summary: "Retrieve a collection of labels",
            operationId: "listLabels",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "include",
                    in: "query",
                    description: "The comma-separated list of included entities",
                    example: "highlights"
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of labels.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Label")
                    )
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
                )
            ]
        )]
        public function listLabels(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelRead);

            $include = $this->getQueryParameter($request, "include") ?? "";

            $requestedIncludes = array_map(fn($entity) => LabelIncludedEntity::from($entity), array_filter(explode(",", $include)));
            $allowedIncludes = array_filter($requestedIncludes, function($entity) use(&$request) {
                $requiredRole = match($entity) {
                    LabelIncludedEntity::Highlights => UserRole::LabelHighlightRead,
                    default => null
                };

                return $requiredRole === null || $this->hasRole($request, $requiredRole);
            });

            // TODO: Do not use the backing value, refactor the service code first.
            $mappedInclude = array_map(fn($include) => $include->value, $allowedIncludes);

            return array_map(fn($label) => $this->filterLabelPermissions($label, $request), $this->labelService->getAllLabels($mappedInclude));
        }

        #[OA\Get(
            path: "/labels/{labelId}",
            summary: "Retrieve a label with the specified identifier",
            operationId: "getLabel",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a label with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Label")
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
        public function getLabel(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelRead);

            $labelId = $this->requirePathArgument($routeArguments, "labelId");

            $label = $this->labelService->getLabel($labelId);
            if ($label === null) {
                throw new NotFoundException($labelId);
            }

            return $this->filterLabelPermissions($label, $request);
        }

        #[OA\Patch(
            path: "/labels/{labelId}",
            summary: "Update a label with the specified identifier",
            operationId: "updateLabel",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the label",
                            type: "string",
                            example: "Village"
                        ),
                        new OA\Property(
                            property: "mainHighlight",
                            description: "The main highlight of the label",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the main highlight of the label",
                                    type: "string",
                                    example: "f93c6a37-9151-4747-af7f-30eac920216e"
                                )
                            ]
                        ),
                        new OA\Property(
                            property: "metadata",
                            description: "The metadata of the label",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "unicode",
                                    description: "The unicode of the metadata of the label",
                                    type: "string",
                                    example: "1f1ec-1f1e7"
                                )
                            ]
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a label with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Label")
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
        public function updateLabel(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelEdit);

            $wasUpdated = false;

            $labelId = $this->requirePathArgument($routeArguments, "labelId");

            $newName = $this->getJsonBodyField($request, "name");
            if ($newName !== null) {
                $wasUpdated |= $this->labelService->updateLabelName($labelId, $newName);
            }

            $newMainHighlight = $this->getJsonBodyField($request, "mainHighlight");
            if ($newMainHighlight !== null && isset($newMainHighlight["id"])) {
                $wasUpdated |= $this->labelService->updateLabelMainHighlight($labelId, $newMainHighlight["id"]);
            }

            $newMetadata = $this->getJsonBodyField($request, "metadata");
            if ($newMetadata !== null) {
                if (isset($newMetadata["unicode"])) {
                    $wasUpdated |= $this->labelService->updateLabelUnicode($labelId, $newMetadata["unicode"]);
                }
            }

            if (!$wasUpdated) {
                $this->logger->warning("The label with the identifier '{$labelId}' was not updated.");
            }

            $label = $this->labelService->getLabel($labelId);
            if ($label === null) {
                throw new NotFoundException($labelId);
            }

            return $this->filterLabelPermissions($label, $request);
        }

        #[OA\Post(
            path: "/labels/{labelId}/highlights",
            summary: "Create a highlight for a label with the specified identifier",
            operationId: "createLabelHighlight",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["photo"],
                    properties: [
                        new OA\Property(
                            property: "photo",
                            description: "The photo representing the highlight",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the photo representing the highlight",
                                    type: "string",
                                    example: "f93c6a37-9151-4747-af7f-30eac920216e"
                                )
                            ]
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a highlight for a label with the specified identifier.",
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
        public function createLabelHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelHighlightEdit);

            $labelId = $this->requirePathArgument($routeArguments, "labelId");
            $photo = $this->requireJsonBodyField($request, "photo");
            if (!is_array($photo) || !isset($photo["id"])) {
                throw new \InvalidArgumentException("The required request body field 'photo.id' is missing.");
            }

            return $this->highlightService->createLabelHighlight($labelId, $photo["id"]);
        }

        #[OA\Post(
            path: "/labels/{labelId}/highlights/refresh",
            summary: "Refresh highlights for a label with the specified identifier",
            operationId: "refreshLabelHighlights",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "count",
                    in: "query",
                    required: true,
                    description: "The count of highlights to select",
                    schema: new OA\Schema(type: "integer"),
                    example: 15,
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Refreshed highlights for a label with the specified identifier.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Highlight")
                    )
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
        public function refreshLabelHighlights(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelHighlightEdit);

            $labelId = $this->requirePathArgument($routeArguments, "labelId");
            $count = $this->requireQueryParameter($request, "count");

            $this->labelService->refreshLabelHighlights($labelId, $count);

            return $this->labelService->getLabel($labelId)?->getHighlights();
        }

        #[OA\Delete(
            path: "/labels/{labelId}/highlights/{highlightId}",
            summary: "Remove a highlight for a label with the specified identifier",
            operationId: "removeLabelHighlight",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "highlightId",
                    in: "path",
                    required: true,
                    description: "The identifier of the highlight",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a highlight for a label with the specified identifier."
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
        public function removeLabelHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelHighlightEdit);

            $labelId = $this->requirePathArgument($routeArguments, "labelId");
            $highlightId = $this->requirePathArgument($routeArguments, "highlightId");

            $wasRemoved = $this->highlightService->removeLabelHighlight($labelId, $highlightId);
            if (!$wasRemoved) {
                throw new NotFoundException($highlightId);
            }

            return null;
        }

        #[OA\Delete(
            path: "/labels/{labelId}",
            summary: "Remove a label with the specified identifier",
            operationId: "removeLabel",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a label with the specified identifier."
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
        public function removeLabel(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LabelEdit);

            $labelId = $this->requirePathArgument($routeArguments, "labelId");

            $wasRemoved = $this->labelService->removeLabelForAllPlaces($labelId);
            if (!$wasRemoved) {
                throw new NotFoundException($labelId);
            }

            return null;
        }

        private function filterLabelPermissions(Label $label, Request $request) : Label {
            if (!$this->hasRole($request, UserRole::LabelHighlightRead)) {
                $label->resetHighlights();
            }
            return $label;
        }
    }
?>
