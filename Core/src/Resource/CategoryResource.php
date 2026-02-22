<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Common\Routing\NotFoundException;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Category\Category;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Category\CategoryIncludedEntity;
    use Core\Service\Category\CategoryService;
    use Core\Service\Highlight\HighlightService;
    use Monolog\Logger;

    #[OA\Tag(name: "Categories")]
    class CategoryResource extends AbstractResource {

        private readonly CategoryService $categoryService;
        private readonly HighlightService $highlightService;
        private readonly Logger $logger;

        public function __construct(CategoryService $categoryService, HighlightService $highlightService, Logger $logger) {
            $this->categoryService = $categoryService;
            $this->highlightService = $highlightService;
            $this->logger = $logger;
        }

        public static function register(App $app, CategoryService $categoryService, HighlightService $highlightService, Logger $logger) : void {
            $resource = new self($categoryService, $highlightService, $logger);

            $app->group("/categories", function($group) use($resource) {
                $group->get("", [$resource, "listCategories"]);
                $group->get("/{categoryId}", [$resource, "getCategory"]);
                $group->patch("/{categoryId}", [$resource, "updateCategory"]);
                $group->delete("/{categoryId}", [$resource, "removeCategory"]);
                $group->post("/{categoryId}/highlights", [$resource, "createCategoryHighlight"]);
                $group->delete("/{categoryId}/highlights/{highlightId}", [$resource, "removeCategoryHighlight"]);
            });
        }

        #[OA\Get(
            path: "/categories",
            summary: "Retrieve a collection of categories",
            operationId: "listCategories",
            tags: ["Categories"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "country",
                    in: "query",
                    description: "The country of the categories",
                    example: "Czechia"
                ),
                new OA\Parameter(
                    name: "categories",
                    in: "query",
                    description: "The comma-separated list of category categories",
                    example: "country,administrative"
                ),
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
                    description: "Success. Retrieved a collection of categories.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Category")
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
        public function listCategories(Request $request, Response $response, array $routeArguments) : mixed {    
            $this->requireRole($request, UserRole::CategoryRead);

            $country = $this->getQueryParameter($request, "country");
            $categories = $this->getQueryParameter($request, "categories") ?? "";
            $include = $this->getQueryParameter($request, "include") ?? "";

            $requestedIncludes = array_map(fn($entity) => CategoryIncludedEntity::from($entity), array_filter(explode(",", $include)));
            $allowedIncludes = array_filter($requestedIncludes, function($entity) use (&$request) {
                $requiredRole = match($entity) {
                    CategoryIncludedEntity::Statistics => UserRole::CategoryStatisticsRead,
                    CategoryIncludedEntity::Highlights => UserRole::CategoryHighlightRead,
                    default => null
                };

                return $requiredRole === null || $this->hasRole($request, $requiredRole);
            });

            // TODO: Do not use the backing value, refactor the service code first.
            $mappedCategories = array_map(fn($category) => CategoryCategory::from($category)->value, 
                array_filter(explode(",", $categories)));
            // TODO: Do not use the backing value, refactor the service code first.
            $mappedInclude = array_map(fn($include) => $include->value, $allowedIncludes);
            
            return array_map(fn($category) => $this->filterCategoryPermissions($category, $request), $this->categoryService->getCategories($country, $mappedCategories, $mappedInclude));
        }

        #[OA\Get(
            path: "/categories/{categoryId}",
            summary: "Retrieve a category with the specified identifier",
            operationId: "getCategory",
            tags: ["Categories"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "categoryId",
                    in: "path",
                    required: true,
                    description: "The identifier of the category",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a category with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Category")
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
        public function getCategory(Request $request, Response $response, array $routeArguments) : mixed {  
            $this->requireRole($request, UserRole::CategoryRead);
              
            $categoryId = $this->requirePathArgument($routeArguments, "categoryId");
            
            $category = $this->categoryService->getCategory($categoryId);
            if ($category === null) {
                throw new NotFoundException($categoryId);
            }

            return $this->filterCategoryPermissions($category, $request);
        }

        #[OA\Patch(
            path: "/categories/{categoryId}",
            summary: "Update a category with the specified identifier",
            operationId: "updateCategory",
            tags: ["Categories"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the category",
                            type: "string",
                            example: "Czech Republic"
                        ),
                        new OA\Property(
                            property: "category",
                            ref: "#/components/schemas/CategoryCategory"
                        ),
                        new OA\Property(
                            property: "mainHighlight",
                            description: "The main highlight of the category",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the main highlight of the category",
                                    type: "string",
                                    example: "f93c6a37-9151-4747-af7f-30eac920216e"
                                )
                            ]
                        ),
                        new OA\Property(
                            property: "metadata",
                            description: "The metadata of the category",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "color",
                                    description: "The color of the metadata of the category",
                                    type: "string",
                                    example: "#012169"
                                ),
                                new OA\Property(
                                    property: "unicode",
                                    description: "The unicode of the metadata of the category",
                                    type: "string",
                                    example: "1f1ec-1f1e7"
                                ),
                                new OA\Property(
                                    property: "publicHolidaysCalendar",
                                    description: "The public holidays calendar of the metadata of the category",
                                    type: "string",
                                    example: "https://calendar.google.com/calendar/ical/en.uk%23holiday%40group.v.calendar.google.com/public/basic.ics"
                                )
                            ]
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "categoryId",
                    in: "path",
                    required: true,
                    description: "The identifier of the category",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a category with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Category")
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
        public function updateCategory(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::CategoryEdit);

            $wasUpdated = false;

            $categoryId = $this->requirePathArgument($routeArguments, "categoryId");

            $newName = $this->getJsonBodyField($request, "name");
            if ($newName !== null) {
                $wasUpdated |= $this->categoryService->updateCategoryName($categoryId, $newName);
            }

            $newCategory = $this->getJsonBodyField($request, "category");
            if ($newCategory !== null) {
                $wasUpdated |= $this->categoryService->updateCategoryCategory($categoryId, CategoryCategory::from($newCategory));
            }
            
            $newMainHighlight = $this->getJsonBodyField($request, "mainHighlight");
            if ($newMainHighlight !== null && isset($newMainHighlight["id"])) {
                $wasUpdated |= $this->categoryService->updateCategoryMainHighlight($categoryId, $newMainHighlight["id"]);
            }
            
            $newMetadata = $this->getJsonBodyField($request, "metadata");
            if ($newMetadata !== null) {
                if (isset($newMetadata["color"])) {
                    $wasUpdated |= $this->categoryService->updateCategoryColor($categoryId, $newMetadata["color"]);
                }
                
                if (isset($newMetadata["unicode"])) {
                    $wasUpdated |= $this->categoryService->updateCategoryUnicode($categoryId, $newMetadata["unicode"]);
                }
                
                if (isset($newMetadata["publicHolidaysCalendar"])) {
                    $wasUpdated |= $this->categoryService->updateCategoryPublicHolidaysCalendar($categoryId, $newMetadata["publicHolidaysCalendar"]);
                }
            }
            
            if (!$wasUpdated) {
                $this->logger->warning("The category with the identifier '{$categoryId}' was not updated.");
            }
            
            $category = $this->categoryService->getCategory($categoryId);
            if ($category === null) {
                throw new NotFoundException($categoryId);
            }
            
            return $this->filterCategoryPermissions($category, $request);
        }

        #[OA\Delete(
            path: "/categories/{categoryId}",
            summary: "Remove a category with the specified identifier",
            operationId: "removeCategory",
            tags: ["Categories"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "categoryId",
                    in: "path",
                    required: true,
                    description: "The identifier of the category",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a category with the specified identifier."
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
        public function removeCategory(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::CategoryEdit);

            $categoryId = $this->requirePathArgument($routeArguments, "categoryId");
            
            $wasRemoved = $this->categoryService->removeCategory($categoryId);
            if (!$wasRemoved) {
                throw new NotFoundException($categoryId);
            }

            return null;
        }

        #[OA\Post(
            path: "/categories/{categoryId}/highlights",
            summary: "Create a highlight for a category with the specified identifier",
            operationId: "createCategoryHighlight",
            tags: ["Categories"],
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
                    name: "categoryId",
                    in: "path",
                    required: true,
                    description: "The identifier of the category",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a highlight for a category with the specified identifier.",
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
        public function createCategoryHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::CategoryHighlightEdit);

            $categoryId = $this->requirePathArgument($routeArguments, "categoryId");
            $photo = $this->requireJsonBodyField($request, "photo");
            if (!is_array($photo) || !isset($photo["id"])) {
                throw new \InvalidArgumentException("The required request body field 'photo.id' is missing.");
            }

            return $this->highlightService->createCategoryHighlight($categoryId, $photo["id"]);
        }

        #[OA\Delete(
            path: "/categories/{categoryId}/highlights/{highlightId}",
            summary: "Remove a highlight for a category with the specified identifier",
            operationId: "removeCategoryHighlight",
            tags: ["Categories"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "categoryId",
                    in: "path",
                    required: true,
                    description: "The identifier of the category",
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
                    description: "Success. Removed a highlight for a category with the specified identifier."
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
        public function removeCategoryHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::CategoryHighlightEdit);

            $categoryId = $this->requirePathArgument($routeArguments, "categoryId");
            $highlightId = $this->requirePathArgument($routeArguments, "highlightId");

            $wasRemoved = $this->highlightService->removeCategoryHighlight($categoryId, $highlightId);
            if (!$wasRemoved) {
                throw new NotFoundException($highlightId);                
            }

            return null;
        }

        private function filterCategoryPermissions(Category $category, Request $request) : Category {
            if (!$this->hasRole($request, UserRole::CategoryStatisticsRead)) {
                $category->resetStatistics();
            }
            else {
                $category->setStatistics(array_values(array_filter($category->getStatistics(), fn($statistics) => $this->hasRole($request, $statistics->getName()->getRequiredRole()))));
            }
            if (!$this->hasRole($request, UserRole::CategoryHighlightRead)) {
                $category->resetHighlights();
            }
            return $category;
        }
    }
?>