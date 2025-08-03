<?php
    namespace Service\Resource;

    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Service\Routing\NotFoundException;
    use Service\Routing\NotUpdatedException;
    use Service\Service\Category\CategoryCategory;
    use Service\Service\Category\CategoryIdentifier;
    use Service\Service\Category\CategoryIncludedEntity;
    use Service\Service\Category\CategoryService;
    use Service\Service\Category\RegionType;
    use Service\Service\Highlight\HighlightService;

    #[OA\Tag(name: "Categories")]
    class CategoryResource extends AbstractResource {

        private readonly CategoryService $categoryService;
        private readonly HighlightService $highlightService;

        public function __construct(CategoryService $categoryService, HighlightService $highlightService) {
            $this->categoryService = $categoryService;
            $this->highlightService = $highlightService;
        }

        public static function register(App $app, CategoryService $categoryService, HighlightService $highlightService) : void {
            $resource = new self($categoryService, $highlightService);

            $app->group("/categories", function($group) use($resource) {
                $group->post("", [$resource, "createCategory"]); // TODO: Refactor to createRegion?
                $group->get("", [$resource, "listCategories"]);
                $group->get("/{categoryId}", [$resource, "getCategory"]);
                $group->patch("/{categoryId}", [$resource, "updateCategory"]);
                $group->post("/{categoryId}/highlights", [$resource, "createCategoryHighlight"]);
                $group->delete("/{categoryId}/highlights/{highlightId}", [$resource, "removeCategoryHighlight"]);
            });
        }
        
        #[OA\Post(
            path: "/categories",
            summary: "Create a category",
            operationId: "createCategory",
            tags: ["Categories"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    required: true,
                    description: "The type of the region",
                    schema: new OA\Schema(ref: "#/components/schemas/RegionType"),
                    example: "geographical",
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["name", "category"],
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the category",
                            type: "string",
                            example: "Europe"
                        ),
                        new OA\Property(
                            property: "category",
                            description: "The category of the category",
                            ref: "#/components/schemas/CategoryCategory"
                        ),
                        new OA\Property(
                            property: "country",
                            description: "The country of the category (required for geographical regions)",
                            type: "string",
                            example: "United States"
                        ),
                        new OA\Property(
                            property: "latitude",
                            description: "The latitude coordinate of the point region (required for geographical extension regions)",
                            type: "number",
                            format: "float",
                            example: 50.100833
                        ),
                        new OA\Property(
                            property: "longitude",
                            description: "The longitude coordinate of the point region (required for geographical extension regions)",
                            type: "number",
                            format: "float",
                            example: 14.26
                        ),
                        new OA\Property(
                            property: "radius",
                            type: "integer",
                            description: "The radius of the geographical region in kilometers (required for geographical regions)",
                            example: 5
                        ),
                        new OA\Property(
                            property: "geoJson",
                            type: "object",
                            description: "The GeoJSON object defining the shape of the geographical region (required for geographical regions)",
                            example: '{"type":"Polygon","coordinates":[[[14.4,50.0],[14.5,50.0],[14.5,50.1],[14.4,50.1],[14.4,50.0]]]}'
                        ),
                        new OA\Property(
                            property: "includedRegions",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            description: "The list of included regions (required for composite regions)",
                            example: '["Eastern Europe"]'
                        ),
                        new OA\Property(
                            property: "excludedRegions",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            description: "The list of excluded regions (required for composite regions)",
                            example: '["Czech Republic", "Slovakia", "Poland", "Hungary"]'
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The category was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/CategoryIdentifier")
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
        public function createCategory(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $name = $this->validateJsonBodyField($request, "name");
            $category = CategoryCategory::from($this->validateJsonBodyField($request, "category"));        
            $regionType = RegionType::from($this->validateQueryParameter($request, "type"));

            return match($regionType) {
                RegionType::Geographical => $this->handleCreateGeographicalRegion($request, $name, $category),
                RegionType::GeographicalExtension => $this->handleCreateGeographicalExtensionRegion($request, $name, $category),
                RegionType::Composie => $this->handleCreateCompositeRegion($request, $name, $category)
            };
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
                    description: "The country of the category",
                    example: "Czechia"
                ),
                new OA\Parameter(
                    name: "categories",
                    in: "query",
                    description: "The comma-separated list of category categories",
                    example: "COUNTRY,ADMINISTRATIVE"
                ),
                new OA\Parameter(
                    name: "include",
                    in: "query",
                    description: "The comma-separated list of included entities",
                    example: "HIGHLIGHTS,STATISTICS"
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
            $country = $this->validateQueryNullableParameter($request, "country");
            $categories = $this->validateQueryNullableParameter($request, "categories") ?? "";
            $include = $this->validateQueryNullableParameter($request, "include") ?? "";

            // TODO: Do not use the backing value, refactor the service code first.
            $mappedCategories = array_map(fn($category) => CategoryCategory::from($category)->value, 
                array_filter(explode(",", $categories)));
            // TODO: Do not use the backing value, refactor the service code first.
            $mappedInclude = array_map(fn($entity) => CategoryIncludedEntity::from($entity)->value, 
                array_filter(explode(",", $include)));
            
            return $this->categoryService->getCategories($country, $mappedCategories, $mappedInclude);
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
            $categoryId = $this->validatePathArgument($routeArguments, "categoryId");
            
            $category = $this->categoryService->getCategory($categoryId);
            if ($category === NULL) {
                throw new NotFoundException($categoryId);
            }

            return $category;
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
            $this->validateAdminPermissions($request);

            $categoryId = $this->validatePathArgument($routeArguments, "categoryId");

            $newName = $this->validateJsonBodyNullableField($request, "name");
            if ($newName !== NULL) {
                $wasUpdated = $this->categoryService->updateCategoryName($categoryId, $newName);
                if (!$wasUpdated) {
                    throw new NotUpdatedException($categoryId);
                }
            }
            
            $newMainHighlight = $this->validateJsonBodyNullableField($request, "mainHighlight");
            if ($newMainHighlight !== NULL && isset($newMainHighlight["id"])) {
                $wasUpdated = $this->categoryService->updateCategoryMainHighlight($categoryId, $newMainHighlight["id"]);
                if (!$wasUpdated) {
                    throw new NotUpdatedException($categoryId);
                }
            }
            
            $newMetadata = $this->validateJsonBodyNullableField($request, "metadata");
            if ($newMetadata !== NULL) {
                if (isset($newMetadata["color"])) {
                    $wasUpdated = $this->categoryService->updateCategoryColor($categoryId, $newMetadata["color"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($categoryId);
                    }
                }
                
                if (isset($newMetadata["unicode"])) {
                    $wasUpdated = $this->categoryService->updateCategoryUnicode($categoryId, $newMetadata["unicode"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($categoryId);
                    }
                }
                
                if (isset($newMetadata["publicHolidaysCalendar"])) {
                    $wasUpdated = $this->categoryService->updateCategoryPublicHolidaysCalendar($categoryId, $newMetadata["publicHolidaysCalendar"]);
                    if (!$wasUpdated) {
                        throw new NotUpdatedException($categoryId);
                    }
                }
            }
            
            $category = $this->categoryService->getCategory($categoryId);
            if ($category === NULL) {
                throw new NotFoundException($categoryId);
            }

            return $category;
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
            $this->validateAdminPermissions($request);

            $categoryId = $this->validatePathArgument($routeArguments, "categoryId");
            $photo = $this->validateJsonBodyField($request, "photo");
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
            $this->validateAdminPermissions($request);

            $categoryId = $this->validatePathArgument($routeArguments, "categoryId");
            $highlightId = $this->validatePathArgument($routeArguments, "highlightId");

            $wasRemoved = $this->highlightService->removeCategoryHighlight($categoryId, $highlightId);
            if (!$wasRemoved) {
                throw new NotFoundException($highlightId);                
            }

            return NULL;
        }

        private function handleCreateGeographicalRegion(Request $request, string $name, CategoryCategory $category) : CategoryIdentifier {
            $country = $this->validateJsonBodyNullableField($request, "country");
            $radius = $this->validateJsonBodyField($request, "radius");
            $geoJson = $this->validateJsonBodyField($request, "geoJson");
            
            return $this->categoryService->createGeographicalRegion($name, $country, $category->value, $radius, $geoJson);
        }

        private function handleCreateGeographicalExtensionRegion(Request $request, string $name, CategoryCategory $category) : CategoryIdentifier {
            $country = $this->validateJsonBodyNullableField($request, "country");
            $latitude = $this->validateJsonBodyField($request, "latitude");
            $longitude = $this->validateJsonBodyField($request, "longitude");
            
            return $this->categoryService->createGeographicalRegionExtensionRegion($name, $country, $category->value, $latitude, $longitude);
        }

        private function handleCreateCompositeRegion(Request $request, string $name, CategoryCategory $category) : CategoryIdentifier {
            $includedRegions = $this->validateJsonBodyField($request, "includedRegions");
            $excludedRegions = $this->validateJsonBodyField($request, "excludedRegions");
            
            return $this->categoryService->createCompositeRegion($name, $category->value, $includedRegions, $excludedRegions);
        }
    }
?>