<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Lifestyle\LifestyleEventType;
    use Core\Service\Lifestyle\LifestyleService;
    use OpenApi\Attributes as OA;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Lifestyle")]
    class LifestyleResource extends AbstractResource {

        private readonly LifestyleService $lifestyleService;

        public function __construct(LifestyleService $lifestyleService) {
            $this->lifestyleService = $lifestyleService;
        }

        public static function register(App $app, LifestyleService $lifestyleService) : void {
            $resource = new self($lifestyleService);
            $app->group("/lifestyle", function($group) use($resource) {
                $group->post("", [$resource, "createLifestyleEvent"]);
                $group->get("", [$resource, "listLifestyleEvents"]);
                $group->get("/{lifestyleEventId}", [$resource, "getLifestyleEvent"]);
                $group->patch("/{lifestyleEventId}", [$resource, "updateLifestyleEvent"]);
                $group->delete("/{lifestyleEventId}", [$resource, "removeLifestyleEvent"]);
            });
        }

        #[OA\Post(
            path: "/lifestyle",
            summary: "Create a lifestyle event",
            operationId: "createLifestyleEvent",
            tags: ["Lifestyle"],
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["timestamp", "type"],
                    properties: [
                        new OA\Property(
                            property: "type",
                            description: "The type of the lifestyle event",
                            ref: "#/components/schemas/LifestyleEventType"
                        ),
                        new OA\Property(
                            property: "timestamp",
                            type: "integer",
                            description: "The time of the lifestyle event in epoch seconds",
                            example: 1753912800
                        ),
                        new OA\Property(
                            property: "content",
                            type: "string",
                            description: "The content of the lifestyle event",
                            example: "Big Mac Meal"
                        ),
                        new OA\Property(
                            property: "calories",
                            type: "number",
                            format: "float",
                            description: "The calories of the lifestyle event",
                            example: 1250.8
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. The lifestyle event was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/LifestyleEvent")
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
        public function createLifestyleEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LifestyleEdit);

            $type = $this->requireJsonBodyField($request, "type");
            $timestamp = $this->requireJsonBodyField($request, "timestamp");
            $content = $this->getJsonBodyField($request, "content");
            $calories = $this->getJsonBodyField($request, "calories");

            $mappedType = LifestyleEventType::from($type);

            return $this->lifestyleService->createLifestyleEvent($mappedType, $timestamp, $content, $calories);
        }

        #[OA\Get(
            path: "/lifestyle",
            operationId: "listLifestyleEvents",
            summary: "List lifestyle events for an interval",
            tags: ["Lifestyle"],
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(
                    name: "from",
                    in: "query",
                    description: "The minimum timestamp of the lifestyle events in epoch seconds",
                    example: 1689786000
                ),
                new OA\Parameter(
                    name: "to",
                    in: "query",
                    description: "The maximum timestamp of the lifestyle events in epoch seconds",
                    example: 1689786000
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    description: "The type of the time tracking event",
                    schema: new OA\Schema(ref: "#/components/schemas/LifestyleEventType")
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of lifestyle events.",
                    content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/LifestyleEvent"))
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
        public function listLifestyleEvents(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LifestyleRead);

            $type = $this->getQueryParameter($request, "type");
            $from = $this->getQueryParameter($request, "from");
            $to = $this->getQueryParameter($request, "to");
            
            $mappedType = $type !== null ? LifestyleEventType::from($type) : null;

            return $this->lifestyleService->getLifestyleEvents($mappedType, $from, $to);
        }

        #[OA\Get(
            path: "/lifestyle/{lifestyleEventId}",
            summary: "Retrieve a lifestyle event with the specified identifier",
            operationId: "getLifestyleEvent",
            tags: ["Lifestyle"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "lifestyleEventId",
                    in: "path",
                    required: true,
                    description: "The identifier of the lifestyle event",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a lifestyle event with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/LifestyleEvent")
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
        public function getLifestyleEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LifestyleRead);

            $lifestyleEventId = $this->requirePathArgument($routeArguments, "lifestyleEventId");
            
            $lifestyleEvent = $this->lifestyleService->getLifestyleEvent($lifestyleEventId);
            if ($lifestyleEvent === null) {
                throw new NotFoundException($lifestyleEventId);
            }

            return $lifestyleEvent;
        }

        #[OA\Patch(
            path: "/lifestyle/{lifestyleEventId}",
            summary: "Update a lifestyle event with the specified identifier",
            operationId: "updateLifestyleEvent",
            tags: ["Lifestyle"],
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(
                    name: "lifestyleEventId",
                    in: "path",
                    required: true,
                    description: "The identifier of the lifestyle event",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a"
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["content"],
                    properties: [
                        new OA\Property(
                            property: "content",
                            type: "string",
                            description: "The content of the lifestyle event",
                            example: "Big Mac Meal"
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a lifestyle event with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/LifestyleEvent")
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
        public function updateLifestyleEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LifestyleEdit);

            $wasUpdated = false;

            $lifestyleEventId = $this->requirePathArgument($routeArguments, "lifestyleEventId");
            
            $newContent = $this->getJsonBodyField($request, "content");
            if ($newContent !== null) {
                $wasUpdated |= $this->lifestyleService->updateLifestyleEventContent($lifestyleEventId, $newContent);
            }

            $lifestyleEvent = $this->lifestyleService->getLifestyleEvent($lifestyleEventId);
            if ($lifestyleEvent === null) {
                throw new NotFoundException($lifestyleEventId);
            }

            return $lifestyleEvent;
        }

        #[OA\Delete(
            path: "/lifestyle/{lifestyleEventId}",
            summary: "Remove a lifestyle event with the specified identifier",
            operationId: "removeLifestyleEvent",
            tags: ["Lifestyle"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "lifestyleEventId",
                    in: "path",
                    required: true,
                    description: "The identifier of the lifestyle event",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a lifestyle event with the specified identifier."
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
        public function removeLifestyleEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::LifestyleEdit);

            $lifestyleEventId = $this->requirePathArgument($routeArguments, "lifestyleEventId");

            $wasRemoved = $this->lifestyleService->removeLifestyleEvent($lifestyleEventId);
            if (!$wasRemoved) {
                throw new NotFoundException($lifestyleEventId);
            }

            return null;
        }
    }
?>
