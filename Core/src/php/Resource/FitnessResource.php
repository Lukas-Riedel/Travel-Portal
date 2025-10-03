<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Common\Routing\NotUpdatedException;
    use Core\Service\Fitness\FitnessService;

    #[OA\Tag(name: "Fitness")]
    class FitnessResource extends AbstractResource {

        private readonly FitnessService $fitnessService;

        public function __construct(FitnessService $fitnessService) {
            $this->fitnessService = $fitnessService;
        }

        public static function register(App $app, FitnessService $fitnessService) : void {
            $resource = new self($fitnessService);

            $app->group("/fitness", function($group) use($resource) {
                $group->put("/{timestamp}", [$resource, "replaceFitness"]);
            });
        }

        #[OA\Put(
            path: "/fitness/{timestamp}",
            summary: "Replace a fitness record with the specified timestamp",
            operationId: "replaceFitness",
            tags: ["Fitness"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: "#/components/schemas/Fitness")
            ),
            parameters: [
                new OA\Parameter(
                    name: "timestamp",
                    in: "path",
                    required: true,
                    description: "The timestamp of the record",
                    schema: new OA\Schema(type: "string"),
                    example: "1754252500",
                ),
                new OA\Parameter(
                    name: "forceOverwrite",
                    in: "query",
                    description: "Whether the fitness record should be forcibly overwritten or not",
                    schema: new OA\Schema(type: "boolean")
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a fitness record with the specified timestamp.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Fitness")
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
        public function replaceFitness(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $timestamp = $this->requirePathArgument($routeArguments, "timestamp");
            $steps = $this->requireJsonBodyField($request, "steps");
            $distance = $this->requireJsonBodyField($request, "distance");
            $seconds = $this->requireJsonBodyField($request, "seconds");
            $forceOverwrite = filter_var($this->getQueryParameter($request, "forceOverwrite") ?? "false", FILTER_VALIDATE_BOOLEAN);

            $wasReplaced = $this->fitnessService->updateFitnessRecord($timestamp, $steps, $seconds, $distance, $forceOverwrite);
            if (!$wasReplaced) {
                throw new NotUpdatedException($timestamp);
            }

            return $this->requireJsonBody($request);
        }
    }
?>