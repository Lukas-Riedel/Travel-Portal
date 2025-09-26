<?php
    namespace Core\Resource;

    use Core\Event\EventPublisher;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Events")]
    class EventResource extends AbstractResource {

        private readonly EventPublisher $eventPublisher;

        public function __construct(EventPublisher $eventPublisher) {
            $this->eventPublisher = $eventPublisher;
        }

        public static function register(App $app, EventPublisher $eventPublisher) : void {
            $resource = new self($eventPublisher);

            $app->group("/events", function($group) use($resource) {
                $group->post("", [$resource, "createEvent"]);
                $group->post("/webhook", [$resource, "createWebhookEvent"]);
            });
        }

        #[OA\Post(
            path: "/events",
            summary: "Create an event",
            operationId: "createEvent",
            tags: ["Events"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["name"],
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the event",
                            type: "string",
                            example: "CalendarInvalidated"
                        ),
                        new OA\Property(
                            property: "args",
                            description: "The arguments of the event",
                            type: "object",
                            example: '{"calendar":"trips"}'
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. The event was created."
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
        public function createEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $name = $this->validateJsonBodyField($request, "name");
            $args = $this->validateJsonBodyNullableField($request, "args");

            $this->eventPublisher->publishRawEvent($name, $args);

            return null;
        }
        
        #[OA\Post(
            path: "/events/webhook",
            summary: "Create a webhook event",
            operationId: "createWebhookEvent",
            tags: ["Events"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "eventId",
                    in: "query",
                    required: true,
                    description: "The identifier of the webhook event",
                    example: "4b340550242239.64159797"
                ),
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. The webhook event was created."
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
                )
            ]
        )]
        public function createWebhookEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $eventId = $this->validateQueryParameter($request, "eventId");

            $this->eventPublisher->publishStoredEvent($eventId);

            return null;
        }
    }
?>