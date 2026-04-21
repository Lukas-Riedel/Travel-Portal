<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Category\CategoryService;
    use Core\Service\Flight\FlightService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Index\IndexableEntityType;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Core\Service\Index\IndexService;
    use Core\Service\Index\SearchResult;
    use Core\Service\Label\LabelService;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Trip\TripService;
    use Core\Service\Year\YearService;

    #[OA\Tag(name: "Search")]
    class SearchResource extends AbstractResource {

        private const DEFAULT_SEARCH_RESULTS_COUNT = 10;

        private readonly IndexService $indexService;
        private readonly CategoryService $categoryService;
        private readonly PlaceService $placeService;
        private readonly FlightService $flightService;
        private readonly LabelService $labelService;
        private readonly TripService $tripService;
        private readonly YearService $yearService;
        private readonly PhotoService $photoService;
        private readonly HighlightService $highlightService;

        public function __construct(IndexService $indexService, CategoryService $categoryService, PlaceService $placeService,
            FlightService $flightService, LabelService $labelService, TripService $tripService, YearService $yearService,
            PhotoService $photoService, HighlightService $highlightService) {
            $this->indexService = $indexService;
            $this->categoryService = $categoryService;
            $this->placeService = $placeService;
            $this->flightService = $flightService;
            $this->labelService = $labelService;
            $this->tripService = $tripService;
            $this->yearService = $yearService;
            $this->photoService = $photoService;
            $this->highlightService = $highlightService;
        }

        public static function register(App $app, IndexService $indexService, CategoryService $categoryService, PlaceService $placeService,
            FlightService $flightService, LabelService $labelService, TripService $tripService, YearService $yearService,
            PhotoService $photoService, HighlightService $highlightService) : void {
            $resource = new self($indexService, $categoryService, $placeService, $flightService, $labelService, $tripService, $yearService,
                $photoService, $highlightService);

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
                    name: "include",
                    in: "query",
                    description: "The comma-separated list of included entities",
                    example: "place,category"
                ),
                new OA\Parameter(
                    name: "limit",
                    in: "query",
                    description: "The count of search results",
                    example: "10"                
                ),
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
            $include = $this->getQueryParameter($request, "include");
            $limit = $this->getQueryParameter($request, "limit") ?? self::DEFAULT_SEARCH_RESULTS_COUNT;

            $allowedEntityTypes = IndexableEntityType::cases();
            if ($include !== null) {
                $allowedEntityTypes = array_map(fn($entityType) => IndexableEntityType::from($entityType), explode(",", $include));
            }
            
            $allowedEntityTypes = array_filter($allowedEntityTypes, fn($entityType) => $this->hasRole($request, $entityType->getRequiredRole()));

            $searchResults = array_map(fn($searchResult) => $this->mapSearchResult($request, $searchResult), $this->indexService->search($query, $limit, $allowedEntityTypes, $this->hasRole($request, UserRole::SearchEdit)));
            return array_values(array_filter($searchResults, fn($searchResult) => $searchResult->getEntity() !== null));
        }

        private function mapSearchResult(Request $request, SearchResult $searchResult) : SearchResult {
            $mappedSerachResult = $this->getEntity($searchResult->getType(), $searchResult->getEntity());
            if ($searchResult->getParent() === null || !$this->hasRole($request, $searchResult->getParent()->getType()->getRequiredRole())) {
                return $searchResult->withReplacedParentAndEntity(null, $mappedSerachResult);
            }

            return $searchResult->withReplacedParentAndEntity($this->mapSearchResult($request, $searchResult->getParent()), $mappedSerachResult);
        }

        private function getEntity(IndexableEntityType $entityType, string $entityId) : mixed {            
            return match ($entityType) {
                IndexableEntityType::Category => $this->categoryService->getCategoryIdentifierById($entityId),
                IndexableEntityType::Place => $this->placeService->getPlaceIdentifierById($entityId),
                IndexableEntityType::Airport => $this->flightService->getAirportIdentifier($entityId),
                IndexableEntityType::Airline => $this->flightService->getAirlineIdentifier($entityId),
                IndexableEntityType::Label => $this->labelService->getLabel($entityId),
                IndexableEntityType::Trip => $this->tripService->getTripIdentifierById($entityId),
                IndexableEntityType::Year => $this->yearService->getYearIdentifier($entityId),
                IndexableEntityType::Photo => $this->photoService->getPhoto($entityId),
                IndexableEntityType::Highlight => $this->highlightService->getHighlight($entityId),
            };
        }
    }
?>