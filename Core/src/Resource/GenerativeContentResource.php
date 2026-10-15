<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Client\Embedding\EmbeddingClient;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Client\GenerativeContent\GenerativeContentResult;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Index\IndexService;
    use OpenApi\Attributes as OA;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Generative Content")]
    class GenerativeContentResource extends AbstractResource {
    
        private const CHAT_RESPONSE_JSON_SCHEMA = array(
            "type" => "object",
            "properties" => array(
                "message" => array(
                    "type" => "string",
                    "description" => "The full response text in Markdown. Named entities (trips, places, categories, …) are replaced by placeholder tokens of the form {{P_1}}, {{P_2}}, etc. that the frontend will expand into clickable links."
                ),
                "placeholders" => array(
                    "type" => "object",
                    "description" => "Map from placeholder key (e.g. \"P_1\") to the entity it represents. Only include placeholders that are actually used in the message field.",
                    "additionalProperties" => array(
                        "type" => "object",
                        "properties" => array(
                            "entityType" => array("type" => "string", "description" => "The entity type as used in the Travel Portal URL (e.g. \"trip\", \"place\", \"category\", \"airport\", \"airline\", \"label\", \"year\")."),
                            "entityId"   => array("type" => "string", "description" => "The UUID or identifier of the entity exactly as returned by the tool that fetched it. Never invent an ID."),
                            "label"      => array("type" => "string", "description" => "The human-readable display name for the link text, in the language of the user's message.")
                        ),
                        "required" => array("entityType", "entityId", "label")
                    )
                ),
                "image_queries" => array(
                    "type" => "array",
                    "description" => "Zero to three short natural-language image search queries (e.g. \"beach in Vietnam\", \"street food Hanoi\"). Populate only when showing photos genuinely adds value — scenic trips, places, visual highlights. Leave empty for factual, numerical or administrative answers.",
                    "items" => array("type" => "string", "description" => "A concise descriptive query such as \"rice terraces in Bali\" or \"old town Prague at night\". Never include placeholder tokens here.")
                )
            ),
            "required" => array("message", "placeholders", "image_queries")
        );

        private const MAX_IMAGE_QUERIES = 3;
        private const IMAGE_CANDIDATES_COEFFICIENT = 3;

        private readonly GenerativeContentClient $generativeContentClient;
        private readonly EmbeddingClient $embeddingClient;
        private readonly IndexService $indexService;
        private readonly HighlightService $highlightService;

        public function __construct(GenerativeContentClient $generativeContentClient, EmbeddingClient $embeddingClient, IndexService $indexService, HighlightService $highlightService) {
            $this->generativeContentClient = $generativeContentClient;
            $this->embeddingClient = $embeddingClient;
            $this->indexService = $indexService;
            $this->highlightService = $highlightService;
        }

        public static function register(App $app, GenerativeContentClient $generativeContentClient, EmbeddingClient $embeddingClient, IndexService $indexService, HighlightService $highlightService) : void {
            $resource = new self($generativeContentClient, $embeddingClient, $indexService, $highlightService);

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

            $rawResult = $this->generativeContentClient->getChatResponse($prompt, $userInfo->getUserId(), $userInfo->getUsername(), $conversationId, self::CHAT_RESPONSE_JSON_SCHEMA, $environment);
            if ($rawResult === null) {
                return null;
            }

            return $this->enrichResult($rawResult);
        }

        private function enrichResult(GenerativeContentResult $rawResult) : GenerativeContentResult {
            $decoded = json_decode($rawResult->getContent(), true);

            if (!is_array($decoded) || !isset($decoded["message"])) {
                // Fallback: malformed JSON — return raw content with empty placeholders/imageUrls.
                return new GenerativeContentResult($rawResult->getContent(), $rawResult->getConversationId(), array(), array());
            }

            $message = $decoded["message"] ?? "";
            $placeholders = is_array($decoded["placeholders"]) ? $decoded["placeholders"] : array();
            $imageQueries = is_array($decoded["image_queries"]) ? array_slice($decoded["image_queries"], 0, self::MAX_IMAGE_QUERIES) : array();

            $imageUrls = $this->resolveImageUrls($imageQueries);

            return new GenerativeContentResult($message, $rawResult->getConversationId(), $placeholders, $imageUrls);
        }

        private function resolveImageUrls(array $imageQueries) : array {
            $imageUrls = array();

            foreach ($imageQueries as &$query) {
                if (!is_string($query) || $query === "") {
                    continue;
                }

                $embedding = $this->embeddingClient->getTextEmbedding($query);
                if ($embedding === null) {
                    continue;
                }

                $neighbours = $this->indexService->getNearestNeighbourHighlightIds($embedding, 1, self::IMAGE_CANDIDATES_COEFFICIENT, true, false, true);
                if (empty($neighbours)) {
                    continue;
                }

                $highlight = $this->highlightService->getHighlight($neighbours[0]->getEntityId());
                if ($highlight === null) {
                    continue;
                }

                $thumbnailUrl = $highlight->getUrl()->getThumbnail();
                if ($thumbnailUrl !== null) {
                    $imageUrls[] = $thumbnailUrl;
                }
            }

            return $imageUrls;
        }
    }
?>
