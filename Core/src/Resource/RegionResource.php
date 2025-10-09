<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Category\CategoryService;
    use Core\Service\Category\CompositeRegion;
    use Core\Service\Category\GeographicalRegion;
    use Core\Service\Category\RegionType;

    #[OA\Tag(name: "Regions")]
    class RegionResource extends AbstractResource {

        private readonly CategoryService $categoryService;

        public function __construct(CategoryService $categoryService) {
            $this->categoryService = $categoryService;
        }

        public static function register(App $app, CategoryService $categoryService) : void {
            $resource = new self($categoryService);

            $app->group("/regions", function($group) use($resource) {
                $group->post("", [$resource, "createRegion"]);
            });
        }
        
        #[OA\Post(
            path: "/regions",
            summary: "Create a region",
            operationId: "createRegion",
            tags: ["Regions"],
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
                    required: ["category"],
                    properties: [
                        new OA\Property(
                            property: "category",
                            description: "The category of the region",
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
                                )
                            ]
                        ),
                        new OA\Property(
                            property: "countryCategory",
                            description: "The country category of the region",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "name",
                                    description: "The name of the country category",
                                    type: "string",
                                    example: "Europe"
                                )
                            ]
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
                            property: "includedCategories",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "name", type: "string", example: "Eastern Europe")
                                ]
                            ),
                            description: "The list of included categories (required for composite regions)",
                        ),
                        new OA\Property(
                            property: "excludedCategories",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "name", type: "string", example: "Czech Republic")
                                ]
                            ),
                            description: "The list of excluded categories (required for composite regions)",
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The region was created.",
                    content: new OA\JsonContent(
                        oneOf: [
                            new OA\Schema(ref: "#/components/schemas/GeographicalRegion"),
                            new OA\Schema(ref: "#/components/schemas/CompositeRegion"),
                        ]
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
        public function createRegion(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $category = $this->requireJsonBodyField($request, "category");
            
            if (!isset($category["name"])) {
                throw new \InvalidArgumentException("The required request body field 'category.name' is missing.");
            }
            $name = $category["name"];

            if (!isset($category["category"])) {
                throw new \InvalidArgumentException("The required request body field 'category.category' is missing.");
            }
            $categoryCategory = CategoryCategory::from($category["category"]);        
            
            $regionType = RegionType::from($this->requireQueryParameter($request, "type"));

            return match ($regionType) {
                RegionType::Geographical => $this->handleCreateGeographicalRegion($request, $name, $categoryCategory),
                RegionType::GeographicalExtension => $this->handleCreateGeographicalExtensionRegion($request, $name, $categoryCategory),
                RegionType::Composite => $this->handleCreateCompositeRegion($request, $name, $categoryCategory)
            };
        }

        private function handleCreateGeographicalRegion(Request $request, string $name, CategoryCategory $category) : GeographicalRegion {
            $countryCategory = $this->getJsonBodyField($request, "countryCategory");
            $country = is_array($countryCategory) ? ($countryCategory["name"] ?? null) : null;

            $radius = $this->requireJsonBodyField($request, "radius");
            $geoJson = $this->requireJsonBodyField($request, "geoJson");
            
            return $this->categoryService->createGeographicalRegion($name, $country, $category->value, $radius, $geoJson);
        }

        private function handleCreateGeographicalExtensionRegion(Request $request, string $name, CategoryCategory $category) : GeographicalRegion {
            $countryCategory = $this->getJsonBodyField($request, "countryCategory");
            $country = is_array($countryCategory) ? ($countryCategory["name"] ?? null) : null;

            $latitude = $this->requireJsonBodyField($request, "latitude");
            $longitude = $this->requireJsonBodyField($request, "longitude");
            
            return $this->categoryService->createGeographicalRegionExtensionRegion($name, $country, $category->value, $latitude, $longitude);
        }

        private function handleCreateCompositeRegion(Request $request, string $name, CategoryCategory $category) : CompositeRegion {
            $includedCategories = array_map(function($category) {
                if (!isset($category["name"])) {
                    throw new \InvalidArgumentException("The required request body field 'includedCategories.name' is missing.");
                }
                return $category["name"];
            }, $this->requireJsonBodyField($request, "includedCategories"));
            
            $excludedCategories = array_map(function($category) {
                if (!isset($category["name"])) {
                    throw new \InvalidArgumentException("The required request body field 'excludedCategories.name' is missing.");
                }
                return $category["name"];
            }, $this->requireJsonBodyField($request, "excludedCategories"));
            
            return $this->categoryService->createCompositeRegion($name, $category->value, $includedCategories, $excludedCategories);
        }
    }
?>