<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Service\Authentication\UserRole;
    use Core\Service\Statistics\StatisticsService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Statistics")]
    class StatisticsResource extends AbstractResource {
        
        private readonly StatisticsService $statisticsService;

        public function __construct(StatisticsService $statisticsService) {
            $this->statisticsService = $statisticsService;
        }

        public static function register(App $app, StatisticsService $statisticsService) : void {
            $resource = new self($statisticsService);

            $app->group("/statistics", function($group) use($resource) {
                $group->get("", [$resource, "listStatistics"]);
            });
        }

        #[OA\Get(
            path: "/statistics",
            summary: "Retrieve a collection of statistics records",
            operationId: "listStatistics",
            tags: ["Statistics"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of statistics records.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Statistics")
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
        public function listStatistics(Request $request, Response $response, array $routeArguments) : mixed {   
            $this->requireRole($request, UserRole::StatisticsRead);

            return $this->statisticsService->getOverallStatistics();
        }  
    }
?>