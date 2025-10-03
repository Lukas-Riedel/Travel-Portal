<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Core\Service\Flight\FlightService;
    use Monolog\Logger;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Airports")]
    class AirportResource extends AbstractResource {
        
        private readonly FlightService $flightService;
        private readonly Logger $logger;

        public function __construct(FlightService $flightService, Logger $logger) {
            $this->flightService = $flightService;
            $this->logger = $logger;
        }

        public static function register(App $app, FlightService $flightService, Logger $logger) : void {
            $resource = new self($flightService, $logger);

            $app->group("/airports", function($group) use($resource) {
                $group->get("", [$resource, "listAirports"]);
                $group->get("/{airportId}", [$resource, "getAirport"]);
                $group->patch("/{airportId}", [$resource, "updateAirport"]);
            });
        }

        #[OA\Get(
            path: "/airports",
            summary: "Retrieve a collection of airports",
            operationId: "listAirports",
            tags: ["Airports"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of airports.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Airport")
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
        public function listAirports(Request $request, Response $response, array $routeArguments) : mixed {            
            return $this->flightService->getAllAirports();
        }        

        #[OA\Get(
            path: "/airports/{airportId}",
            summary: "Retrieve an airport with the specified identifier",
            operationId: "getAirport",
            tags: ["Airports"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "airportId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airport",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a airport with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Airport")
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
        public function getAirport(Request $request, Response $response, array $routeArguments) : mixed {    
            $airportId = $this->requirePathArgument($routeArguments, "airportId");
            
            $airport = $this->flightService->getAirport($airportId);
            if ($airport === null) {
                throw new NotFoundException($airportId);
            }

            return $airport;
        }

        #[OA\Patch(
            path: "/airports/{airportId}",
            summary: "Update an airport with the specified identifier",
            operationId: "updateAirport",
            tags: ["Airports"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "longName",
                            description: "The long name of the airport",
                            type: "string",
                            example: "Dubai International Airport"
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "airportId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airport",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a airport with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Airport")
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
        public function updateAirport(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);
            $wasUpdated = false;

            $airportId = $this->requirePathArgument($routeArguments, "airportId");
            
            $newLongName = $this->getJsonBodyField($request, "longName");
            if ($newLongName !== null) {
                $wasUpdated |= $this->flightService->updateAirportName($airportId, $newLongName);
            }

            if (!$wasUpdated) {
                $this->logger->warning("The airport with the identifier '{$airportId}' was not updated.");
            }

            $airport = $this->flightService->getAirport($airportId);
            if ($airport === null) {
                throw new NotFoundException($airportId);
            }

            return $airport;
        }
    }
?>