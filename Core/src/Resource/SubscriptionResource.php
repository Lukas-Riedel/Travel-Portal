<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Expense\ExpenseService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Subscriptions")]
    class SubscriptionResource extends AbstractResource {
        
        private readonly ExpenseService $expenseService;

        public function __construct(ExpenseService $expenseService) {
            $this->expenseService = $expenseService;
        }

        public static function register(App $app, ExpenseService $expenseService) : void {
            $resource = new self($expenseService);

            $app->group("/subscriptions", function($group) use($resource) {
                $group->post("", [$resource, "createSubscription"]);
                $group->get("", [$resource, "listSubscriptions"]);
                $group->get("/{subscriptionId}", [$resource, "getSubscription"]);
                $group->delete("/{subscriptionId}", [$resource, "removeSubscription"]);
            });
        }
        
        #[OA\Post(
            path: "/subscriptions",
            summary: "Create a subscription",
            operationId: "createSubscription",
            tags: ["Subscriptions"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: [ "description", "value", "currency", "expiration" ],
                    properties: [
                        new OA\Property(
                            property: "description",
                            type: "string",
                            description: "The description of the subscription",
                            example: "Deutschland Ticket"
                        ),
                        new OA\Property(
                            property: "value",
                            description: "The value of the subscription in the specified currency",
                            type: "number",
                            format: "float",
                            example: 58
                        ),
                        new OA\Property(
                            property: "currency",
                            type: "string",
                            description: "The currency of the subscription",
                            example: "EUR"
                        ),
                        new OA\Property(
                            property: "expiration",
                            type: "integer",
                            description: "The expiration of the subscription in epoch seconds",
                            example: 1753912800
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The subscription was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Subscription")
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
        public function createSubscription(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::SubscriptionEdit);

            $description = $this->requireJsonBodyField($request, "description");
            $value = $this->requireJsonBodyField($request, "value");
            $currency = $this->requireJsonBodyField($request, "currency");
            $expiration = $this->requireJsonBodyField($request, "expiration");

            return $this->expenseService->createSubscription($value, $currency, $description, $expiration);
        }

        #[OA\Get(
            path: "/subscriptions",
            summary: "Retrieve a collection of subscriptions",
            operationId: "listSubscriptions",
            tags: ["Subscriptions"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of subscriptions.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Subscription")
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
        public function listSubscriptions(Request $request, Response $response, array $routeArguments) : mixed { 
            $this->requireRole($request, UserRole::SubscriptionRead);

            return $this->expenseService->getActiveSubscriptions();
        }  

        #[OA\Get(
            path: "/subscriptions/{subscriptionId}",
            summary: "Retrieve a subscription with the specified identifier",
            operationId: "getSubscription",
            tags: ["Subscriptions"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "subscriptionId",
                    in: "path",
                    required: true,
                    description: "The identifier of the subscription",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a subscription with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Subscription")
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
        public function getSubscription(Request $request, Response $response, array $routeArguments) : mixed {    
            $this->requireRole($request, UserRole::SubscriptionRead);

            $subscriptionId = $this->requirePathArgument($routeArguments, "subscriptionId");
            
            $subscription = $this->expenseService->getActiveSubscription($subscriptionId);
            if ($subscription === null) {
                throw new NotFoundException($subscriptionId);
            }

            return $subscription;
        }

        #[OA\Delete(
            path: "/subscriptions/{subscriptionId}",
            summary: "Remove a subscription with the specified identifier",
            operationId: "removeSubscription",
            tags: ["Subscriptions"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "subscriptionId",
                    in: "path",
                    required: true,
                    description: "The identifier of the subscription",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a subscription with the specified identifier."
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
        public function removeSubscription(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::SubscriptionEdit);

            $subscriptionId = $this->requirePathArgument($routeArguments, "subscriptionId");
            
            $wasRemoved = $this->expenseService->removeActiveSubscription($subscriptionId);
            if (!$wasRemoved) {
                throw new NotFoundException($subscriptionId);
            }

            return null;
        }
    }
?>