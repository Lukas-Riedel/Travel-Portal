<?php
    namespace Core\Resource;

    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Core\Service\Geocoding\GeocodingService;

    #[OA\Tag(name: "Geocoding")]
    class GeocodingResource extends AbstractResource {

        private readonly GeocodingService $geocodingService;

        public function __construct(GeocodingService $geocodingService) {
            $this->geocodingService = $geocodingService;
        }

        public static function register(App $app, GeocodingService $geocodingService) : void {
            $resource = new self($geocodingService);

            $app->group("/coordinates", function($group) use($resource) {
                $group->get("", [$resource, "getCoordinates"]);
            });
        }

        #[OA\Get(
            path: "/coordinates",
            summary: "Retrieve coordinates for the specified address",
            operationId: "getCoordinates",
            tags: ["Geocoding"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "address",
                    in: "query",
                    required: true,
                    description: "The address to retrieve coordinates for",
                    example: "Vodičkova 25, 110 00 Prague 1, Czech Republic",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved coordinates for the specified address.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Location")
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
        public function getCoordinates(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $address = $this->validateQueryParameter($request, "address");
            
            return $this->geocodingService->getLocation($address);
        }
    }
?>