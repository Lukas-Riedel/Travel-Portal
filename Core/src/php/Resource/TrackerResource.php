<?php
    namespace Core\Resource;

    use Core\Routing\NotFoundException;
    use Core\Service\TimeTracking\TimeTrackingService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Tracker")]
    class TrackerResource extends AbstractResource {
        
        private readonly TimeTrackingService $timeTrackingService;

        public function __construct(TimeTrackingService $timeTrackingService) {
            $this->timeTrackingService = $timeTrackingService;
        }

        public static function register(App $app, TimeTrackingService $timeTrackingService) : void {
            $resource = new self($timeTrackingService);

            $app->group("/tracker", function($group) use($resource) {
                $group->post("", [$resource, "createTimeTrackingEvent"]);
                $group->get("", [$resource, "listTimeTrackingEvents"]);
                $group->delete("/{timeTrackingEventId}", [$resource, "removeTimeTrackingEvent"]);
            });
        }

        #[OA\Post(
            path: "/tracker",
            summary: "Create a time tracking event",
            operationId: "createTimeTrackingEvent",
            tags: ["Tracker"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: [ "type", "hours", "description", "timestamp" ],
                    properties: [
                        new OA\Property(
                            property: "type",
                            description: "The type of the time tracking event",
                            ref: "#/components/schemas/TimeTrackingEventType"
                        ),
                        new OA\Property(
                            property: "hours",
                            description: "The hours of the time tracking event",
                            type: "number",
                            format: "float",
                            example: 1.6
                        ),
                        new OA\Property(
                            property: "description",
                            type: "string",
                            description: "The description of the time tracking event",
                            example: "Integrating Redis to Scanner Worker"
                        ),
                        new OA\Property(
                            property: "timestamp",
                            type: "integer",
                            description: "The time of the time tracking event in epoch seconds",
                            example: 1753912800
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The time tracking event was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/TimeTrackingEvent")
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
        public function createTimeTrackingEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $type = $this->validateJsonBodyField($request, "type");
            $hours = $this->validateJsonBodyField($request, "hours");
            $description = $this->validateJsonBodyField($request, "description");
            $timestamp = $this->validateJsonBodyField($request, "timestamp");

            return $this->timeTrackingService->createTimeTrackingEvent($type, $hours, $description, $timestamp);
        }

        #[OA\Get(
            path: "/tracker",
            summary: "Retrieve a collection of time tracking events",
            operationId: "listTimeTrackingEvents",
            tags: ["Tracker"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    description: "The type of the time tracking event",
                    schema: new OA\Schema(ref: "#/components/schemas/TimeTrackingEventType")
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of time tracking events.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/TimeTrackingEvent")
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
        public function listTimeTrackingEvents(Request $request, Response $response, array $routeArguments) : mixed {    
            $type = $this->validateQueryNullableParameter($request, "type");

            return $this->timeTrackingService->getTimeTrackingEvents($type);
        }

        #[OA\Delete(
            path: "/tracker/{timeTrackingEventId}",
            summary: "Remove a time tracking event with the specified identifier",
            operationId: "removeTimeTrackingEvent",
            tags: ["Tracker"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "timeTrackingEventId",
                    in: "path",
                    required: true,
                    description: "The identifier of the time tracking event",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a time tracking event with the specified identifier."
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
        public function removeTimeTrackingEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $timeTrackingEventId = $this->validatePathArgument($routeArguments, "timeTrackingEventId");
            
            $wasRemoved = $this->timeTrackingService->removeTimeTrackingEvent($timeTrackingEventId);
            if (!$wasRemoved) {
                throw new NotFoundException($timeTrackingEventId);
            }

            return null;
        }
    }
?>