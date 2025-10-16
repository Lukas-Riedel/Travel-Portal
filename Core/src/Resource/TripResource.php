<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Common\Routing\NotFoundException;
    use Common\Routing\NotUpdatedException;
    use Core\Service\Expense\ExpenseService;
    use Core\Service\Expense\ExpenseType;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Note\NoteService;
    use Core\Service\Trip\Trip;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;
    use Core\Service\Trip\TripType;
    use Monolog\Logger;

    #[OA\Tag(name: "Trips")]
    class TripResource extends AbstractResource {

        private readonly TripService $tripService;
        private readonly ExpenseService $expenseService;
        private readonly NoteService $noteService;
        private readonly HighlightService $highlightService;
        private readonly Logger $logger;

        public function __construct(TripService $tripService, ExpenseService $expenseService, NoteService $noteService, HighlightService $highlightService, Logger $logger) {
            $this->tripService = $tripService;
            $this->expenseService = $expenseService;
            $this->noteService = $noteService;
            $this->highlightService = $highlightService;
            $this->logger = $logger;
        }

        public static function register(App $app, TripService $tripService, ExpenseService $expenseService, NoteService $noteService, HighlightService $highlightService, Logger $logger) : void {
            $resource = new self($tripService, $expenseService, $noteService, $highlightService, $logger);

            $app->group("/trips", function($group) use($resource) {
                $group->get("", [$resource, "listTrips"]);
                $group->get("/{tripId}", [$resource, "getTrip"]);
                $group->put("/{tripId}", [$resource, "replaceTrip"]);
                $group->patch("/{tripId}", [$resource, "updateTrip"]);
                $group->delete("/{tripId}", [$resource, "removeTrip"]);
                $group->post("/{tripId}/expenses", [$resource, "createTripExpense"]);
                $group->patch("/{tripId}/expenses/{expenseId}", [$resource, "updateTripExpense"]);
                $group->delete("/{tripId}/expenses/{expenseId}", [$resource, "removeTripExpense"]);
                $group->post("/{tripId}/notes", [$resource, "createTripNote"]);
                $group->delete("/{tripId}/notes/{noteId}", [$resource, "removeTripNote"]);
                $group->post("/{tripId}/highlights", [$resource, "createTripHighlight"]);
                $group->delete("/{tripId}/highlights/{highlightId}", [$resource, "removeTripHighlight"]);
            });
        }
        
        #[OA\Get(
            path: "/trips",
            summary: "Retrieve a collection of trips",
            operationId: "listTrips",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "year",
                    in: "query",
                    description: "The year of the trips",
                    example: "2025"
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    description: "The type of the trip",
                    schema: new OA\Schema(ref: "#/components/schemas/TripType")                    
                ),
                new OA\Parameter(
                    name: "include",
                    in: "query",
                    description: "The comma-separated list of included entities",
                    example: "highlights,statistics"
                ),
                new OA\Parameter(
                    name: "sort",
                    in: "query",
                    description: "The sorting strategy of the trips",
                    schema: new OA\Schema(ref: "#/components/schemas/TripSortingStrategy"),                    
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of trips.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Trip")
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
        public function listTrips(Request $request, Response $response, array $routeArguments) : mixed {    
            $year = $this->getQueryParameter($request, "year");
            $type = $this->getQueryParameter($request, "type") ?? TripType::Regular->value;
            $include = $this->getQueryParameter($request, "include") ?? "";
            $sort = $this->getQueryParameter($request, "sort") ?? TripSortingStrategy::OldestAscending->value;

            // TODO: Do not use the backing value, refactor the service code first.
            $mappedInclude = array_map(fn($entity) => TripIncludedEntity::from($entity)->value, 
                array_filter(explode(",", $include)));
            $mappedSort = TripSortingStrategy::from($sort);
            $mappedType = TripType::from($type);
            
            return match ($mappedType) {
                TripType::Regular => $this->tripService->getRegularTrips($year, null, null, $mappedInclude, $mappedSort),
                TripType::Candidate => $this->tripService->getCandidateTrips($mappedInclude)
            };
        }

        #[OA\Get(
            path: "/trips/{tripId}",
            summary: "Retrieve a trip with the specified identifier",
            operationId: "getTrip",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a trip with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Trip")
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
        public function getTrip(Request $request, Response $response, array $routeArguments) : mixed {    
            $tripId = $this->requirePathArgument($routeArguments, "tripId");

            return $this->doGetTrip($tripId);
        }

        #[OA\Put(
            path: "/trips/{tripId}",
            summary: "Replace a trip with the specified identifier",
            operationId: "replaceTrip",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["id"],
                    properties: [
                        new OA\Property(
                            property: "id",
                            description: "The identifier of the trip to load",
                            type: "string",
                            example: "47f43337-e0b8-4f3e-a52f-11e6fdf13b02"
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Replaced a trip with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Trip")
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
        public function replaceTrip(Request $request, Response $response, array $routeArguments) : mixed {    
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $candidateTripId = $this->requireJsonBodyField($request, "id");

            $trip = $this->tripService->loadTrip($candidateTripId, $tripId);
            if ($trip === null) {
                throw new NotUpdatedException($tripId);
            }

            return $trip;
        }

        #[OA\Patch(
            path: "/trips/{tripId}",
            summary: "Update a trip with the specified identifier",
            operationId: "updateTrip",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the trip",
                            type: "string",
                            example: "One Thousand Scents of Sri Lanka"
                        ),
                        new OA\Property(
                            property: "start",
                            description: "The start time of the trip in epoch seconds",
                            type: "integer",
                            format: "int64",
                            example: 1688563200
                        ),
                        new OA\Property(
                            property: "mainHighlight",
                            description: "The main highlight of the trip",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the main highlight of the trip",
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
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a trip with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Trip")
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
        public function updateTrip(Request $request, Response $response, array $routeArguments) : mixed {           
            $this->requireAdmin($request);
            $wasUpdated = false;

            $tripId = $this->requirePathArgument($routeArguments, "tripId");

            $newName = $this->getJsonBodyField($request, "name");
            if ($newName !== null) {
                $wasUpdated |= $this->tripService->updateTripName($tripId, $newName);
            }

            $newStart = $this->getJsonBodyField($request, "start");
            if ($newStart !== null) {
                $wasUpdated |= $this->tripService->moveTrip($tripId, $newStart) !== null;
            }
            
            $newMainHighlight = $this->getJsonBodyField($request, "mainHighlight");
            if ($newMainHighlight !== null && isset($newMainHighlight["id"])) {
                $wasUpdated |= $this->tripService->updateTripMainHighlight($tripId, $newMainHighlight["id"]);
            }        
            
            if (!$wasUpdated) {
                $this->logger->warning("The trip with the identifier '{$tripId}' was not updated.");
            }

            return $this->doGetTrip($tripId);
        }

        #[OA\Delete(
            path: "/trips/{tripId}",
            summary: "Remove a trip with the specified identifier",
            operationId: "removeTrip",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a trip with the specified identifier."
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
        public function removeTrip(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");

            $trip = $this->doGetTrip($tripId);
            if ($trip->getYear() == null) {
                $wasRemoved = $this->tripService->removeCandidateTrip($tripId);
                
                if (!$wasRemoved) {
                    throw new NotFoundException($tripId);
                }
            }
            else {
                $archivedTrip = $this->tripService->archiveTrip($tripId);
                
                if ($archivedTrip === null) {
                    throw new NotFoundException($tripId);
                }
            }

            return null;
        }        

        #[OA\Post(
            path: "/trips/{tripId}/expenses",
            summary: "Create an expense for a trip with the specified identifier",
            operationId: "createTripExpense",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["description", "value", "currency", "type", "subscription"],
                    properties: [
                        new OA\Property(
                            property: "description",
                            type: "string",
                            description: "The description of the expense",
                            example: "Hierapolis Archeological Site"
                        ),
                        new OA\Property(
                            property: "value",
                            description: "The value of the expense in the specified currency",
                            type: "number",
                            format: "float",
                            example: 30
                        ),
                        new OA\Property(
                            property: "currency",
                            type: "string",
                            description: "The currency of the expense",
                            example: "EUR"
                        ),
                        new OA\Property(
                            property: "type",
                            description: "The type of the expense",
                            ref: "#/components/schemas/ExpenseType"
                        ),
                        new OA\Property(
                            property: "subscription",
                            description: "The subscription of the expense",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the subscription of the expense",
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
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created an expense for a trip with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Expense")
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
        public function createTripExpense(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $description = $this->requireJsonBodyField($request, "description");
            $value = $this->requireJsonBodyField($request, "value");
            $currency = $this->requireJsonBodyField($request, "currency");
            $type = $this->requireJsonBodyField($request, "type");
            $subscription = $this->getJsonBodyField($request, "subscription");

            $subscriptionId = null;
            if (is_array($subscription) && isset($subscription["id"])) {
                $subscriptionId = $subscription["id"];
            }

            $mappedType = ExpenseType::from($type);

            // TODO: Do not use the backing value, refactor the service code first.
            return $this->expenseService->createExpense($tripId, $value, $currency, $mappedType->value, $description, $subscriptionId);
        }       

        #[OA\Patch(
            path: "/trips/{tripId}/expenses/{expenseId}",
            summary: "Update an expense for a trip with the specified identifier",
            operationId: "updateTripExpense",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "description",
                            type: "string",
                            description: "The description of the expense",
                            example: "Hierapolis Archeological Site"
                        ),
                        new OA\Property(
                            property: "value",
                            description: "The value of the expense in the specified currency",
                            type: "number",
                            format: "float",
                            example: 30
                        ),
                        new OA\Property(
                            property: "currency",
                            type: "string",
                            description: "The currency of the expense",
                            example: "EUR"
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "expenseId",
                    in: "path",
                    required: true,
                    description: "The identifier of the expense",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Updated an expense for a trip with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Expense")
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
        public function updateTripExpense(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);
            $wasUpdated = false;

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $expenseId = $this->requirePathArgument($routeArguments, "expenseId");
            
            $newDescription = $this->getJsonBodyField($request, "description");
            if ($newDescription !== null) {
                $wasUpdated |= $this->expenseService->updateExpenseDescription($expenseId, $newDescription, $tripId); 
            }
            
            $newValue = $this->getJsonBodyField($request, "value");
            if ($newValue !== null) {
                $wasUpdated |= $this->expenseService->updateExpenseValue($expenseId, $newValue, $tripId);
            }
            
            $newCurrency = $this->getJsonBodyField($request, "currency");
            if ($newCurrency !== null) {
                $wasUpdated |= $this->expenseService->updateExpenseCurrency($expenseId, $newCurrency, $tripId);
            }

            if (!$wasUpdated) {
                $this->logger->warning("The expense with the identifier '{$expenseId}' was not updated.");
            }

            $expense = $this->expenseService->getExpense($expenseId);
            if ($expense === null) {
                throw new NotFoundException($expenseId);
            }

            return $expenseId;
        }

        #[OA\Delete(
            path: "/trips/{tripId}/expenses/{expenseId}",
            summary: "Remove an expense for a trip with the specified identifier",
            operationId: "removeTripExpense",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "expenseId",
                    in: "path",
                    required: true,
                    description: "The identifier of the expense",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed an expense for a trip with the specified identifier."
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
        public function removeTripExpense(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $expenseId = $this->requirePathArgument($routeArguments, "expenseId");

            $wasRemoved = $this->expenseService->removeExpense($expenseId, $tripId);
            if (!$wasRemoved) {
                throw new NotFoundException($expenseId);                
            }

            return null;
        }

        #[OA\Post(
            path: "/trips/{tripId}/notes",
            summary: "Create a note for a trip with the specified identifier",
            operationId: "createTripNote",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["content"],
                    properties: [
                        new OA\Property(
                            property: "content",
                            description: "The HTML content of the note",
                            type: "string",
                            example: "<strong>Lorem ipsum</strong> dolor sit amet, consectetur adipiscing elit. Morbi fringilla sem sed nulla luctus iaculis. Cras rutrum turpis massa. Suspendisse."
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a note for a trip with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Note")
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
        public function createTripNote(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $content = $this->requireJsonBodyField($request, "content");

            return $this->noteService->createTripNote($tripId, $content);
        }

        #[OA\Delete(
            path: "/trips/{tripId}/notes/{noteId}",
            summary: "Remove a note for a trip with the specified identifier",
            operationId: "removeTripNote",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "noteId",
                    in: "path",
                    required: true,
                    description: "The identifier of the note",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a note for a trip with the specified identifier."
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
        public function removeTripNote(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $noteId = $this->requirePathArgument($routeArguments, "noteId");

            $wasRemoved = $this->noteService->removeTripNote($tripId, $noteId);
            if (!$wasRemoved) {
                throw new NotFoundException($noteId);                
            }

            return null;
        }

        #[OA\Post(
            path: "/trips/{tripId}/highlights",
            summary: "Create a highlight for a trip with the specified identifier",
            operationId: "createTripHighlight",
            tags: ["Trips"],
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
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a highlight for a trip with the specified identifier.",
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
        public function createTripHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $photo = $this->requireJsonBodyField($request, "photo");
            if (!is_array($photo) || !isset($photo["id"])) {
                throw new \InvalidArgumentException("The required request body field 'photo.id' is missing.");
            }

            return $this->highlightService->createTripHighlight($tripId, $photo["id"]);
        }

        #[OA\Delete(
            path: "/trips/{tripId}/highlights/{highlightId}",
            summary: "Remove a highlight for a trip with the specified identifier",
            operationId: "removeTripHighlight",
            tags: ["Trips"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "tripId",
                    in: "path",
                    required: true,
                    description: "The identifier of the trip",
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
                    description: "Success. Removed a highlight for a trip with the specified identifier."
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
        public function removeTripHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $tripId = $this->requirePathArgument($routeArguments, "tripId");
            $highlightId = $this->requirePathArgument($routeArguments, "highlightId");

            $wasRemoved = $this->highlightService->removeTripHighlight($tripId, $highlightId);
            if (!$wasRemoved) {
                throw new NotFoundException($highlightId);                
            }

            return null;
        }

        private function doGetTrip(string $tripId) : Trip {
            $trip = $this->tripService->getRegularTrip($tripId);
            if ($trip !== null) {
                return $trip;
            }
            
            $trip = $this->tripService->getCandidateTrip($tripId);
            if ($trip !== null) {
                return $trip;
            }

            throw new NotFoundException($tripId);
        }
    }
?>