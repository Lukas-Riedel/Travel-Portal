<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Core\Service\Label\LabelService;
    use Monolog\Logger;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Labels")]
    class LabelResource extends AbstractResource {
        
        private readonly LabelService $labelService;
        private readonly Logger $logger;

        public function __construct(LabelService $labelService, Logger $logger) {
            $this->labelService = $labelService;
            $this->logger = $logger;
        }

        public static function register(App $app, LabelService $labelService, Logger $logger) : void {
            $resource = new self($labelService, $logger);

            $app->group("/labels", function($group) use($resource) {
                $group->get("", [$resource, "listLabels"]);
                $group->get("/{labelId}", [$resource, "getLabel"]);
                $group->patch("/{labelId}", [$resource, "updateLabel"]);
            });
        }

        #[OA\Get(
            path: "/labels",
            summary: "Retrieve a collection of labels",
            operationId: "listLabels",
            tags: ["Labels"],
            security: [ ["bearerAuth" => []] ],
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
            return $this->labelService->getAllLabels();
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
            $labelId = $this->requirePathArgument($routeArguments, "labelId");
            
            $label = $this->labelService->getLabel($labelId);
            if ($label === null) {
                throw new NotFoundException($labelId);
            }

            return $label;
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
            $this->requireAdmin($request);
            $wasUpdated = false;

            $labelId = $this->requirePathArgument($routeArguments, "labelId");
            
            $newName = $this->getJsonBodyField($request, "name");
            if ($newName !== null) {
                $wasUpdated |= $this->labelService->updateLabelName($labelId, $newName);
            }

            if (!$wasUpdated) {
                $this->logger->warning("The label with the identifier '{$labelId}' was not updated.");
            }

            $label = $this->labelService->getLabel($labelId);
            if ($label === null) {
                throw new NotFoundException($labelId);
            }

            return $label;
        }
    }
?>