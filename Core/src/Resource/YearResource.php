<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Common\Routing\NotFoundException;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Year\Year;
    use Core\Service\Year\YearIncludedEntity;
    use Core\Service\Year\YearService;
    use Monolog\Logger;

    #[OA\Tag(name: "Years")]
    class YearResource extends AbstractResource {

        private readonly YearService $yearService;
        private readonly HighlightService $highlightService;
        private readonly Logger $logger;

        public function __construct(YearService $yearService, HighlightService $highlightService, Logger $logger) {
            $this->yearService = $yearService;
            $this->highlightService = $highlightService;
            $this->logger = $logger;
        }

        public static function register(App $app, YearService $yearService, HighlightService $highlightService, Logger $logger) : void {
            $resource = new self($yearService, $highlightService, $logger);

            $app->group("/years", function($group) use($resource) {
                $group->get("", [$resource, "listYears"]);
                $group->get("/{year}", [$resource, "getYear"]);
                $group->patch("/{year}", [$resource, "updateYear"]);
                $group->post("/{year}/highlights", [$resource, "createYearHighlight"]);
                $group->post("/{year}/highlights/refresh", [$resource, "refreshYearHighlights"]);
                $group->delete("/{year}/highlights/{highlightId}", [$resource, "removeYearHighlight"]);
            });
        }
        
        #[OA\Get(
            path: "/years",
            summary: "Retrieve a collection of years",
            operationId: "listYears",
            tags: ["Years"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "include",
                    in: "query",
                    description: "The comma-separated list of included entities",
                    example: "highlights,statistics"
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of years.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Year")
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
        public function listYears(Request $request, Response $response, array $routeArguments) : mixed { 
            $this->requireRole($request, UserRole::YearRead);

            $include = $this->getQueryParameter($request, "include") ?? "";
            
            $requestedIncludes = array_map(fn($entity) => YearIncludedEntity::from($entity), array_filter(explode(",", $include)));
            $allowedIncludes = array_filter($requestedIncludes, function($entity) use (&$request) {
                $requiredRole = match($entity) {
                    YearIncludedEntity::Fitness => UserRole::YearFitnessRead,
                    YearIncludedEntity::Statistics => UserRole::YearStatisticsRead,
                    YearIncludedEntity::Highlights => UserRole::YearHighlightRead,
                    default => null
                };

                return $requiredRole === null || $this->hasRole($request, $requiredRole);
            });
            
            // TODO: Do not use the backing value, refactor the service code first.
            $mappedInclude = array_map(fn($include) => $include->value, $allowedIncludes);
            
            return array_map(fn($year) => $this->filterYearPermissions($year, $request), $this->yearService->getYears($mappedInclude));
        }

        #[OA\Get(
            path: "/years/{year}",
            summary: "Retrieve a year with the specified identifier",
            operationId: "getYear",
            tags: ["Years"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "year",
                    in: "path",
                    required: true,
                    description: "The identifier of the year",
                    schema: new OA\Schema(type: "integer"),
                    example: "2025",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a year with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Year")
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
        public function getYear(Request $request, Response $response, array $routeArguments) : mixed { 
            $this->requireRole($request, UserRole::YearRead);

            $yearId = $this->requirePathArgument($routeArguments, "year");
            
            $year = $this->yearService->getYear($yearId);
            if ($year === null) {
                throw new NotFoundException($yearId);
            }

            return $this->filterYearPermissions($year, $request);
        }

        #[OA\Patch(
            path: "/years/{year}",
            summary: "Update a year with the specified identifier",
            operationId: "updateYear",
            tags: ["Years"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "mainHighlight",
                            description: "The main highlight of the year",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the main highlight of the year",
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
                    name: "year",
                    in: "path",
                    required: true,
                    description: "The identifier of the year",
                    schema: new OA\Schema(type: "integer"),
                    example: "2025",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a year with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Year")
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
        public function updateYear(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::YearEdit);

            $wasUpdated = false;

            $yearId = $this->requirePathArgument($routeArguments, "year");
            
            $newMainHighlight = $this->getJsonBodyField($request, "mainHighlight");
            if ($newMainHighlight !== null && isset($newMainHighlight["id"])) {
                $wasUpdated |= $this->yearService->updateYearMainHighlight($yearId, $newMainHighlight["id"]);
            }
            
            if (!$wasUpdated) {
                $this->logger->warning("The year '{$yearId}' was not updated.");
            }
                        
            $year = $this->yearService->getYear($yearId);
            if ($year === null) {
                throw new NotFoundException($yearId);
            }

            return $this->filterYearPermissions($year, $request);
        }

        #[OA\Post(
            path: "/years/{year}/highlights",
            summary: "Create a highlight for a year with the specified identifier",
            operationId: "createYearHighlight",
            tags: ["Years"],
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
                    name: "year",
                    in: "path",
                    required: true,
                    description: "The identifier of the year",
                    schema: new OA\Schema(type: "integer"),
                    example: "2025",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a highlight for a year with the specified identifier.",
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
        public function createYearHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::YearHighlightEdit);

            $yearId = $this->requirePathArgument($routeArguments, "year");
            $photo = $this->requireJsonBodyField($request, "photo");
            if (!is_array($photo) || !isset($photo["id"])) {
                throw new \InvalidArgumentException("The required request body field 'photo.id' is missing.");
            }

            return $this->highlightService->createYearHighlight($yearId, $photo["id"]);
        }

        #[OA\Post(
            path: "/years/{yearId}/highlights/refresh",
            summary: "Refresh highlights for a year with the specified identifier",
            operationId: "refreshYearHighlights",
            tags: ["Years"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "year",
                    in: "path",
                    required: true,
                    description: "The identifier of the year",
                    schema: new OA\Schema(type: "integer"),
                    example: "2025",
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
                    description: "Success. Refreshed highlights for a year with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Album")
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
        public function refreshYearHighlights(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::YearHighlightEdit);

            $year = $this->requirePathArgument($routeArguments, "year");
            $count = $this->requireQueryParameter($request, "count");

            $this->yearService->refreshYearHighlights($year, $count);
            
            return $this->yearService->getYear($year)?->getHighlights();
        }

        #[OA\Delete(
            path: "/years/{year}/highlights/{highlightId}",
            summary: "Remove a highlight for a year with the specified identifier",
            operationId: "removeYearHighlight",
            tags: ["Years"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "year",
                    in: "path",
                    required: true,
                    description: "The identifier of the year",
                    schema: new OA\Schema(type: "integer"),
                    example: "2025",
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
                    description: "Success. Removed a highlight for a year with the specified identifier."
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
        public function removeYearHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::YearHighlightEdit);

            $yearId = $this->requirePathArgument($routeArguments, "year");
            $highlightId = $this->requirePathArgument($routeArguments, "highlightId");

            $wasRemoved = $this->highlightService->removeYearHighlight($yearId, $highlightId);
            if (!$wasRemoved) {
                throw new NotFoundException($highlightId);                
            }

            return null;
        }
        
        private function filterYearPermissions(Year $year, Request $request) : Year {
            if (!$this->hasRole($request, UserRole::YearStatisticsRead)) {
                $year->resetStatistics();
            }
            else {
                $year->setStatistics(array_values(array_filter($year->getStatistics(), fn($statistics) => $this->hasRole($request, $statistics->getName()->getRequiredRole()))));
            }
            if (!$this->hasRole($request, UserRole::YearHighlightRead)) {
                $year->resetHighlights();
            }
            if (!$this->hasRole($request, UserRole::YearFitnessRead)) {
                $year->resetFitness();
            }
            return $year;
        }
    }
?>