<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Client\GenerativeContent\GenerativeContentResult;
    use Core\Service\Category\CategoryService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Index\IndexService;
    use Core\Service\Label\LabelService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;
    use Core\Service\Year\YearService;
    use OpenApi\Attributes as OA;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Generative Content")]
    class GenerativeContentResource extends AbstractResource {

        // TODO: Move this to the request, the UI should decide this.
        private const IMAGE_COUNT = 3;

        private readonly GenerativeContentClient $generativeContentClient;
        private readonly IndexService $indexService;
        private readonly HighlightService $highlightService;
        private readonly TripService $tripService;
        private readonly PlaceService $placeService;
        private readonly CategoryService $categoryService;
        private readonly LabelService $labelService;
        private readonly YearService $yearService;

        public function __construct(GenerativeContentClient $generativeContentClient, IndexService $indexService, HighlightService $highlightService,
            TripService $tripService, PlaceService $placeService, CategoryService $categoryService, LabelService $labelService, YearService $yearService) {
            $this->generativeContentClient = $generativeContentClient;
            $this->indexService = $indexService;
            $this->highlightService = $highlightService;
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->categoryService = $categoryService;
            $this->labelService = $labelService;
            $this->yearService = $yearService;
        }

        public static function register(App $app, GenerativeContentClient $generativeContentClient, IndexService $indexService, HighlightService $highlightService,
            TripService $tripService, PlaceService $placeService, CategoryService $categoryService, LabelService $labelService, YearService $yearService) : void {
            $resource = new self($generativeContentClient, $indexService, $highlightService, $tripService, $placeService, $categoryService, $labelService, $yearService);

            $app->group("/generativecontent", function($group) use($resource) {
                $group->post("", [$resource, "createGenerativeContent"]);
            });
        }

        #[OA\Post(
            path: "/generativecontent",
            summary: "Create a generative content conversation",
            operationId: "createGenerativeContent",
            tags: ["Generative Content"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: [ "prompt" ],
                    properties: [
                        new OA\Property(
                            property: "prompt",
                            type: "string",
                            description: "The prompt to send to the generative model",
                            example: "What is the capital of France?"
                        ),
                        new OA\Property(
                            property: "conversationId",
                            type: "string",
                            description: "The optional identifier of the existing conversation",
                            example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
                        ),
                        new OA\Property(
                            property: "environment",
                            type: "object",
                            description: "Optional arbitrary JSON subtree with environment data passed to the generative engine (e.g. timezone)",
                            example: ["timezone" => "Europe/Prague"]
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a generative content conversation.",
                    content: new OA\JsonContent(ref: "#/components/schemas/GenerativeContentResult")
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
        public function createGenerativeContent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireRole($request, UserRole::GenerativeContentEdit);

            $prompt = $this->requireJsonBodyField($request, "prompt");
            $conversationId = $this->getJsonBodyField($request, "conversationId");
            $environment = $this->getJsonBodyField($request, "environment");
            $userInfo = $this->getUserInfo($request);

            $rawResult = $this->generativeContentClient->getChatResponse($prompt, $userInfo->getUserId(), $userInfo->getUsername(), $conversationId, null, $environment);
            if ($rawResult === null) {
                return null;
            }

            return $this->enrichResult($rawResult);
        }

        private function enrichResult(GenerativeContentResult $rawResult) : GenerativeContentResult {
            $decoded = json_decode($rawResult->getContent(), true);

            if (!is_array($decoded) || !isset($decoded["text"])) {
                // Fallback: malformed JSON - return raw content with empty placeholders/imageUrls.
                return new GenerativeContentResult($rawResult->getContent(), $rawResult->getConversationId(), array(), array());
            }

            $placeholders = array();
            foreach (is_array($decoded["placeholders"]) ? $decoded["placeholders"] : array() as $entry) {
                if (isset($entry["key"], $entry["entityType"], $entry["entityId"], $entry["label"])) {
                    $placeholders[$entry["key"]] = array(
                        "entityType" => $entry["entityType"],
                        "entityId" => $entry["entityId"],
                        "label" => $entry["label"],
                    );
                }
            }

            return new GenerativeContentResult($decoded["text"], $rawResult->getConversationId(), $placeholders, $this->resolveImageUrls($placeholders));
        }

        private function resolveImageUrls(array $placeholders) : array {
            $placeholdersImageUrls = array();

            foreach ($placeholders as &$placeholder) {
                $entityType = $placeholder["entityType"] ?? null;
                $entityId = $placeholder["entityId"] ?? null;
                if ($entityType === null || $entityId === null || isset($placeholdersImageUrls[$entityId])) {
                    continue;
                }

                $placeholdersImageUrls[$entityId] = match($entityType) {
                    "trip" => $this->resolveTripImageUrls($entityId),
                    "place" => $this->resolvePlaceImageUrls($entityId),
                    "category" => $this->resolveCategoryImageUrls($entityId),
                    "label" => $this->resolveLabelImageUrls($entityId),
                    "year" => $this->resolveYearImageUrls($entityId),
                    default => array()
                };                
            }
            
            $imageUrls = array();

            for ($i = 0; $i < self::IMAGE_COUNT; ++$i) {
                foreach ($placeholders as &$placeholder) {
                    $entityId = $placeholder["entityId"] ?? null;
                    $imageUrl = $placeholdersImageUrls[$entityId][$i] ?? null;

                    if ($imageUrl !== null) {
                        $imageUrls[] = $imageUrl;
                    }

                    if (count($imageUrls) >= self::IMAGE_COUNT) {
                        break;
                    }
                }

                if (count($imageUrls) >= self::IMAGE_COUNT) {
                    break;
                }
            }

            return $imageUrls;
        }

        private function resolveTripImageUrls(string $tripId) : array {
            $trip = $this->tripService->getRegularTrip($tripId);
            if ($trip === null) {
                return array();
            }

            $highlightPhotoIds = array_map(fn($h) => $h->getPhoto()->getId(), $trip->getHighlights());
            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForTrip(
                $tripId,
                $trip->getName(),
                count($highlightPhotoIds),
                $trip->getMainHighlight()?->getPhoto()?->getId(),
                $highlightPhotoIds
            );

            return $this->resolvePhotoIdThumbnails(array_intersect($selectedPhotoIds, $highlightPhotoIds));
        }

        private function resolvePlaceImageUrls(string $placeId) : array {
            $place = $this->placeService->getRegularPlace($placeId);
            if ($place === null) {
                return array();
            }

            $highlightPhotoIds = array_map(fn($h) => $h->getPhoto()->getId(), $place->getHighlights());
            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForPlace(
                $placeId,
                $place->getName(),
                count($highlightPhotoIds),
                $place->getMainHighlight()?->getPhoto()?->getId()
            );

            return $this->resolvePhotoIdThumbnails(array_intersect($selectedPhotoIds, $highlightPhotoIds));
        }

        private function resolveCategoryImageUrls(string $categoryId) : array {
            $category = $this->categoryService->getCategory($categoryId);
            if ($category === null) {
                return array();
            }

            $highlightPhotoIds = array_map(fn($h) => $h->getPhoto()->getId(), $category->getHighlights());
            $placeIds = $this->categoryService->getPlaceIdsForCategoryId($categoryId);

            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForCategory(
                $placeIds,
                $category->getName(),
                count($highlightPhotoIds),
                $category->getMainHighlight()?->getPhoto()?->getId(),
                $highlightPhotoIds
            );

            return $this->resolvePhotoIdThumbnails(array_intersect($selectedPhotoIds, $highlightPhotoIds));
        }

        private function resolveLabelImageUrls(string $labelId) : array {
            $label = $this->labelService->getLabel($labelId);
            if ($label === null) {
                return array();
            }

            $highlightPhotoIds = array_map(fn($h) => $h->getPhoto()->getId(), $label->getHighlights());
            $placeIds = $this->labelService->getPlaceIdsForLabelId($labelId);

            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForLabel(
                $placeIds,
                count($highlightPhotoIds),
                $label->getMainHighlight()?->getPhoto()?->getId(),
                $highlightPhotoIds
            );

            return $this->resolvePhotoIdThumbnails(array_intersect($selectedPhotoIds, $highlightPhotoIds));
        }

        private function resolveYearImageUrls(string $yearId) : array {
            $year = (int) $yearId;
            $yearEntity = $this->yearService->getYear($year);
            if ($yearEntity === null) {
                return array();
            }

            $highlightPhotoIds = array_map(fn($h) => $h->getPhoto()->getId(), $yearEntity->getHighlights());
            $trips = $this->tripService->getRegularTrips($year, null, null, array(TripIncludedEntity::Highlights->value), TripSortingStrategy::OldestAscending);
            $tripMainHighlightPhotoIds = array_values(array_filter(array_map(
                fn($t) => $t->getMainHighlight()?->getPhoto()?->getId(),
                $trips
            )));

            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForYear(
                $year,
                $year,
                count($highlightPhotoIds),
                $yearEntity->getMainHighlight()?->getPhoto()?->getId(),
                $tripMainHighlightPhotoIds
            );

            return $this->resolvePhotoIdThumbnails(array_intersect($selectedPhotoIds, $highlightPhotoIds));
        }

        private function resolvePhotoIdThumbnails(array $photoIds) : array {
            return array_values(array_filter(array_map(
                fn($h) => $h->getUrl()->getThumbnail(),
                $this->highlightService->getHighlightsByPhotoIds(array_values($photoIds))
            )));
        }
    }
?>
