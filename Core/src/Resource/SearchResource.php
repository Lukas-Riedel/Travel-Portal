<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Core\Service\Index\IndexableEntityType;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Core\Service\Index\IndexService;

    #[OA\Tag(name: "Search")]
    class SearchResource extends AbstractResource {

        private const DEFAULT_SEARCH_RESULTS_COUNT = 10;

        private readonly IndexService $indexService;

        public function __construct(IndexService $indexService) {
            $this->indexService = $indexService;
        }

        public static function register(App $app, IndexService $indexService) : void {
            $resource = new self($indexService);

            $app->group("/search", function($group) use($resource) {
                $group->get("", [$resource, "listSearchResults"]);
            });
        }

        #[OA\Get(
            path: "/search",
            summary: "Retrieve a collection of search results",
            operationId: "listSearchResults",
            tags: ["Search"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "query",
                    in: "query",
                    required: true,
                    description: "The search query",
                    example: "Prague"                
                ),
                new OA\Parameter(
                    name: "limit",
                    in: "query",
                    description: "The count of search results",
                    example: "10"                
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of search results.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/SearchResult")
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
                )
            ]
        )]
        public function listSearchResults(Request $request, Response $response, array $routeArguments) : mixed {   
            $query = $this->requireQueryParameter($request, "query");
            $limit = $this->getQueryParameter($request, "limit") ?? self::DEFAULT_SEARCH_RESULTS_COUNT;
            $allowedEntityTypes = array_filter(IndexableEntityType::cases(), fn($entityType) => $this->hasRole($request, $entityType->getRequiredRole()));

            return $this->indexService->search($query, $limit, $allowedEntityTypes);
        }
    }
?>