<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Service\GenerativeContent\GenerativeContentService;
    use OpenApi\Attributes as OA;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Generative Content")]
    class GenerativeContentResource extends AbstractResource {

        private readonly GenerativeContentService $generativeContentService;

        public function __construct(GenerativeContentService $generativeContentService) {
            $this->generativeContentService = $generativeContentService;
        }

        public static function register(App $app, GenerativeContentService $generativeContentService) : void {
            $resource = new self($generativeContentService);

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
                        ),
                        new OA\Property(
                            property: "requestedHighlightsCount",
                            type: "integer",
                            description: "The count of requested highlights",
                            example: 3
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a generative content conversation.",
                    content: new OA\JsonContent(ref: "#/components/schemas/ChatResponse")
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
            $this->requireRole($request, UserRole::GenerativeContentRead);

            $prompt = $this->requireJsonBodyField($request, "prompt");
            $conversationId = $this->getJsonBodyField($request, "conversationId");
            $environment = $this->getJsonBodyField($request, "environment");
            $requestedHighlightsCount = $this->getJsonBodyField($request, "requestedHighlightsCount");

            return $this->generativeContentService->getChatResponse($prompt, $this->getAccessToken($request),
                $this->getUserInfo($request)->getUsername(), $conversationId, $environment, $requestedHighlightsCount ?? 0);
        }
    }
?>
