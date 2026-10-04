<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use OpenApi\Attributes as OA;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    #[OA\Tag(name: "Generative Content")]
    class GenerativeContentResource extends AbstractResource {

        private readonly GenerativeContentClient $generativeContentClient;

        public function __construct(GenerativeContentClient $generativeContentClient) {
            $this->generativeContentClient = $generativeContentClient;
        }

        public static function register(App $app, GenerativeContentClient $generativeContentClient) : void {
            $resource = new self($generativeContentClient);

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

            return $this->generativeContentClient->getChatResponse($prompt, $conversationId);
        }
    }
?>
