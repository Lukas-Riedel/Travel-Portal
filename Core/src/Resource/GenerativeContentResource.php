<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Client\GenerativeContent\GenerativeContentResult;
    use Core\Service\Category\CategoryService;
    use Core\Service\Label\LabelService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Trip\TripService;
    use OpenApi\Attributes as OA;
    use Ramsey\Uuid\Uuid;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Generative Content")]
    class GenerativeContentResource extends AbstractResource {

        // TODO: Move this to the request, the UI should decide this.
        private const IMAGE_COUNT = 3;

        private readonly GenerativeContentClient $generativeContentClient;
        private readonly TripService $tripService;
        private readonly PlaceService $placeService;
        private readonly CategoryService $categoryService;
        private readonly LabelService $labelService;

        public function __construct(GenerativeContentClient $generativeContentClient, TripService $tripService, PlaceService $placeService,
            CategoryService $categoryService, LabelService $labelService) {
            $this->generativeContentClient = $generativeContentClient;
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->categoryService = $categoryService;
            $this->labelService = $labelService;
        }

        public static function register(App $app, GenerativeContentClient $generativeContentClient, TripService $tripService, PlaceService $placeService,
            CategoryService $categoryService, LabelService $labelService) : void {
            $resource = new self($generativeContentClient, $tripService, $placeService, $categoryService, $labelService);

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

            $rawResult = $this->generativeContentClient->getChatResponse($prompt, $this->getAccessToken($request), $this->getUserInfo($request)->getUsername(), $conversationId, $environment);
            if ($rawResult === null) {
                return null;
            }

            return $this->enrichResult($rawResult);
        }

        private function enrichResult(GenerativeContentResult $result) : GenerativeContentResult {
            $lastUserMessage = $this->getLastUserMessage($result->getConversationId());
            if ($lastUserMessage === null) {
                return $result;
            }

            $entityIdCandidates = $this->extractUuids($lastUserMessage);
            if (count($entityIdCandidates) > self::IMAGE_COUNT) {
                return $result;
            }

            $highlightsLists = array();
            foreach ($entityIdCandidates as &$entityIdCandidate) {                
                $resolvedEntity = $this->placeService->getRegularPlace($entityIdCandidate)
                    ?? $this->tripService->getRegularTrip($entityIdCandidate)
                    ?? $this->categoryService->getCategory($entityIdCandidate)
                    ?? $this->labelService->getLabel($entityIdCandidate);

                if ($resolvedEntity === null) {
                    continue;
                }

                $highlights = $resolvedEntity->getHighlights();
                if (!empty($highlights)) {
                    shuffle($highlights);
                    $highlightsLists[] = $highlights;
                }           
            }
            shuffle($highlightsLists);

            
            $highlights = array();
            for ($i = 0; $i < self::IMAGE_COUNT; ++$i) {
                foreach ($highlightsLists as &$highlightsList) {
                    if (isset($highlightsList[$i])) {
                        $highlights[$highlightsList[$i]->getId()] = $highlightsList[$i];

                        if (count($highlights) >= self::IMAGE_COUNT) {
                            break 2;
                        }
                    }
                }
            }

            // TODO: Currently, this doesn't respect roles for reading highlights.
            return $result->withHighlights(array_values($highlights));
        }

        private function getLastUserMessage(string $conversationId) : mixed {
            $messages = $this->generativeContentClient->getChatHistory($conversationId);

            foreach (array_reverse($messages) as &$message) {
                if (isset($message["role"]) && $message["role"] === "user") {
                    return $message;
                }
            }
            
            return null;
        }

        private function extractUuids(mixed $data, bool $unique = true): array {
            $uuids = array();

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_array($value) || is_object($value)) {
                        $uuids = array_merge($uuids, $this->extractUuids($value, false));
                    }
                    else if (is_string($value) && Uuid::isValid($value)) {
                        $uuids[] = $value;
                    }
                }
            }

            return $unique ? array_values(array_unique($uuids)) : $uuids;
        }
    }
?>
