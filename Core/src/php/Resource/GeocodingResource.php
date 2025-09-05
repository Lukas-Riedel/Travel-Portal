<?php
    namespace Core\Resource;

use Core\Routing\NotFoundException;
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

            $app->group("/location", function($group) use($resource) {
                $group->post("/track", [$resource, "trackLocation"]);
                $group->get("", [$resource, "getLocation"]);
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
                    content: new OA\JsonContent(ref: "#/components/schemas/Location")
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
                ),
                new OA\Response(
                    response: 404,
                    description: "Not Found. The requested resource did not exist.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Not Found",
                                ref: "#/components/examples/NotFound"
                            )
                        ]
                    )
                )
            ]
        )]
        public function getCoordinates(Request $request, Response $response, array $routeArguments) : mixed {
            $address = $this->validateQueryParameter($request, "address");
            
            $location = $this->geocodingService->getLocation($address, $this->isAdmin($request));
            if ($location === null) {
                throw new NotFoundException($address);
            }
            return $location;
        }

        #[OA\Post(
            path: "/location/track",
            summary: "Track the location",
            operationId: "trackLocation",
            tags: ["Geocoding"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "latitude",
                    in: "query",
                    required: true,
                    description: "The latitude to track",
                    example: "45.767019213737065"
                ),
                new OA\Parameter(
                    name: "longitude",
                    in: "query",
                    required: true,
                    description: "The longitude to track",
                    example: "4.844979856479775"
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The location was tracked.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Address")
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
        public function trackLocation(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $latitude = $this->validateQueryParameter($request, "latitude");
            $longitude = $this->validateQueryParameter($request, "longitude");
            
            return $this->geocodingService->trackLocation($latitude, $longitude);
        }

        #[OA\Get(
            path: "/location",
            summary: "Retrieve tracked location",
            operationId: "getLocation",
            tags: ["Geocoding"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved tracked location.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Address")
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
                ),
                new OA\Response(
                    response: 404,
                    description: "Not Found. The requested resource did not exist.",
                    content: new OA\JsonContent(
                        ref: "#/components/schemas/RequestError",
                        examples: [
                            new OA\Examples(
                                example: "Not Found",
                                ref: "#/components/examples/NotFound"
                            )
                        ]
                    )
                )
            ]
        )]
        public function getLocation(Request $request, Response $response, array $routeArguments) : mixed {            
            $address = $this->geocodingService->getCurrentAddress();
            if ($address === null) {
                throw new NotFoundException(time());
            }
            return $address;
        }
    }
?>