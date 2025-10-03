<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Core\Service\Monitoring\MonitoringService;

    #[OA\Tag(name: "Monitoring")]
    class MonitoringResource extends AbstractResource {

        private readonly MonitoringService $monitoringService;

        public function __construct(MonitoringService $monitoringService) {
            $this->monitoringService = $monitoringService;
        }

        public static function register(App $app, MonitoringService $monitoringService) : void {
            $resource = new self($monitoringService);

            $app->group("/inconsistencies", function($group) use($resource) {
                $group->get("", [$resource, "listInconsistencies"]);
            });
        }

        #[OA\Get(
            path: "/inconsistencies",
            summary: "Retrieve a collection of data consistency issues",
            operationId: "listInconsistencies",
            tags: ["Monitoring"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of airlines.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/DataConsistencyIssue")
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
        public function listInconsistencies(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);
            
            return $this->monitoringService->getDataConsistencyIssues();
        }
    }
?>