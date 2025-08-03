<?php
    namespace Service\Resource;

    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Service\Routing\NotFoundException;
    use Service\Routing\NotUpdatedException;
    use Service\Service\Flight\FlightService;

    #[OA\Tag(name: "Airlines")]
    class AirlineResource extends AbstractResource {

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }

        public static function register(App $app, FlightService $flightService) : void {
            $resource = new self($flightService);

            $app->group("/airlines", function($group) use($resource) {
                $group->post("", [$resource, "createAirline"]);
                $group->get("", [$resource, "listAirlines"]);
                $group->get("/{airlineId}", [$resource, "getAirline"]);
                $group->patch("/{airlineId}", [$resource, "updateAirline"]);
                $group->delete("/{airlineId}", [$resource, "removeAirline"]);
                $group->get("/{airlineId}/codes", [$resource, "createAirlineCode"]);
                $group->get("/{airlineId}/codes/{airlineCode}", [$resource, "deleteAirlineCode"]);
            });
        }
        
        #[OA\Post(
            path: "/airlines",
            summary: "Create an airline",
            operationId: "createAirline",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["name"],
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the airline",
                            type: "string",
                            example: "WizzAir"
                        ),
                        new OA\Property(
                            property: "logo",
                            description: "The logo of the airline in SVG format",
                            type: "string",
                            example: "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"black\"/></svg>"
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The airline was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Airline")
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
        public function createAirline(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $name = $this->validateJsonBodyField($request, "name");
            $logo = $this->validateJsonBodyNullableField($request, "logo");
            
            return $this->flightService->createAirline($name, $logo);
        }
        
        #[OA\Get(
            path: "/airlines",
            summary: "Retrieve a collection of airlines",
            operationId: "listAirlines",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of airlines.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Airline")
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
        public function listAirlines(Request $request, Response $response, array $routeArguments) : mixed {            
            return $this->flightService->getAllAirlines();
        }
        
        #[OA\Get(
            path: "/airlines/{airlineId}",
            summary: "Retrieve an airline with the specified identifier",
            operationId: "getAirline",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "airlineId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airline",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved an airline with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Airline")
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
        public function getAirline(Request $request, Response $response, array $routeArguments) : mixed {    
            $airlineId = $this->validatePathArgument($routeArguments, "airlineId");
            
            $airline = $this->flightService->getAirline($airlineId);
            if ($airline === NULL) {
                throw new NotFoundException($airlineId);
            }

            return $airline;
        }
        
        #[OA\Patch(
            path: "/airlines/{airlineId}",
            summary: "Update an airline with the specified identifier",
            operationId: "updateAirline",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the airline",
                            type: "string",
                            example: "WizzAir"
                        ),
                        new OA\Property(
                            property: "logo",
                            description: "The logo of the airline in SVG format",
                            type: "string",
                            example: "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"black\"/></svg>"
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "airlineId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airline",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated an airline with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Airline")
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
        public function updateAirline(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $airlineId = $this->validatePathArgument($routeArguments, "airlineId");

            $newName = $this->validateJsonBodyNullableField($request, "name");
            if ($newName !== NULL) {
                $wasUpdated = $this->flightService->updateAirlineName($airlineId, $newName);
                if (!$wasUpdated) {
                    throw new NotUpdatedException($airlineId);
                }
            }

            $newLogo = $this->validateJsonBodyNullableField($request, "logo");
            if ($newLogo !== NULL) {
                $wasUpdated = $this->flightService->updateAirlineLogo($airlineId, $newLogo);
                if (!$wasUpdated) {
                    throw new NotUpdatedException($airlineId);
                }
            }
            
            $airline = $this->flightService->getAirline($airlineId);
            if ($airline === NULL) {
                throw new NotFoundException($airlineId);
            }

            return $airline;
        }
        
        #[OA\Delete(
            path: "/airlines/{airlineId}",
            summary: "Remove an airline with the specified identifier",
            operationId: "removeAirline",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "airlineId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airline",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed an airline with the specified identifier."
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
        public function removeAirline(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $airlineId = $this->validatePathArgument($routeArguments, "airlineId");
            
            $wasRemoved = $this->flightService->removeAirline($airlineId);
            if (!$wasRemoved) {
                throw new NotFoundException($airlineId);
            }

            return NULL;
        }
        
        #[OA\Post(
            path: "/airlines/{airlineId}/codes",
            summary: "Create a code for an airline with the specified identifier",
            operationId: "createAirlineCode",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "airlineId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airline",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["code"],
                    properties: [
                        new OA\Property(
                            property: "code",
                            description: "The IATA code of the airline",
                            type: "string",
                            example: "W5"
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The airline code was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Airline")
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
        public function createAirlineCode(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $airlineId = $this->validatePathArgument($routeArguments, "airlineId");
            $airlineCode = $this->validateJsonBodyField($request, "code");
            
            $airline = $this->flightService->getAirline($airlineId);
            if ($airline === NULL) {
                throw new NotFoundException($airlineId);
            }

            $wasUpdated = $this->flightService->updateAirlineCodeAirline($airlineCode, $airlineId);
            if (!$wasUpdated) {
                throw new NotUpdatedException($airlineCode);                
            }

            return $this->flightService->getAirline($airlineId);
        }
        
        #[OA\Delete(
            path: "/airlines/{airlineId}/codes/{airlineCode}",
            summary: "Delete a code for an airline with the specified identifier",
            operationId: "deleteAirlineCode",
            tags: ["Airlines"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "airlineId",
                    in: "path",
                    required: true,
                    description: "The identifier of the airline",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "airlineCode",
                    in: "path",
                    required: true,
                    description: "The code of the airline",
                    schema: new OA\Schema(type: "string"),
                    example: "W5",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. The airline code was removed."
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
        public function deleteAirlineCode(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $airlineId = $this->validatePathArgument($routeArguments, "airlineId");
            $airlineCode = $this->validateJsonBodyField($request, "code");
            
            $airline = $this->flightService->getAirline($airlineId);
            if ($airline === NULL) {
                throw new NotFoundException($airlineId);
            }

            $wasRemoved = $this->flightService->updateAirlineCodeAirline($airlineCode, NULL);
            if (!$wasRemoved) {
                throw new NotFoundException($airlineCode);                
            }

            return NULL;
        }
    }