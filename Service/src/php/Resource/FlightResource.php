<?php
    namespace Service\Resource;

    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Service\Service\Flight\Flight;
    use Service\Service\Flight\FlightService;
    use Service\Service\Flight\FlightType;

    #[OA\Tag(name: "Flights")]
    class FlightResource extends AbstractResource {

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }

        public static function register(App $app, FlightService $flightService) : void {
            $resource = new self($flightService);

            $app->group("/flights", function($group) use($resource) {
                $group->post("", [$resource, "createFlight"]);
            });
        }
        
        #[OA\Post(
            path: "/flights",
            summary: "Create a flight",
            operationId: "createFlight",
            tags: ["Flights"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    required: true,
                    description: "The type of the flight",
                    schema: new OA\Schema(ref: "#/components/schemas/FlightType"),
                    example: "logged",
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["flight", "from", "to", "scheduledDeparture"],
                    properties: [
                        new OA\Property(
                            property: "flight",
                            description: "The number of the flight",
                            type: "string",
                            example: "EK139"
                        ),
                        new OA\Property(
                            property: "registration",
                            description: "The registration of the aircraft (required for manually logged flights)",
                            type: "string",
                            example: "A6-EOQ"
                        ),
                        new OA\Property(
                            property: "aircraft",
                            description: "The type of the aircraft (required for manually logged flights)",
                            type: "string",
                            example: "A388"
                        ),
                        new OA\Property(
                            property: "from",
                            description: "The departure airport of the flight (name required for all flights, code required for manually logged flights)",
                            ref: "#/components/schemas/Airport"
                        ),
                        new OA\Property(
                            property: "to",
                            description: "The arrival airport of the flight (name required for all flights, code required for manually logged flights)",
                            ref: "#/components/schemas/Airport"
                        ),
                        new OA\Property(
                            property: "scheduledDeparture",
                            description: "The scheduled departure time of the flight in epoch seconds",
                            type: "integer",
                            format: "int64",
                            example: 1688563200
                        ),
                        new OA\Property(
                            property: "scheduledArrival",
                            description: "The scheduled arrival time of the flight in epoch seconds (required for scheduled, watched and manually logged flights)",
                            type: "integer",
                            format: "int64",
                            example: 1688570400
                        ),
                        new OA\Property(
                            property: "actualDeparture",
                            description: "The actual departure time of the flight in epoch seconds (required for manually logged flights)",
                            type: "integer",
                            format: "int64",
                            example: 1688563200
                        ),
                        new OA\Property(
                            property: "actualArrival",
                            description: "The actual arrival time of the flight in epoch seconds (required for manually logged flights)",
                            type: "integer",
                            format: "int64",
                            example: 1688570400
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The flight was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Flight")
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
        public function createFlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $flight = $this->validateJsonBodyField($request, "flight");
            $from = $this->validateJsonBodyField($request, "from");
            if (!is_array($from) || !isset($from["name"])) {
                throw new \InvalidArgumentException("The required request body field 'from.name' is missing.");
            }
            $to = $this->validateJsonBodyField($request, "to");
            if (!is_array($to) || !isset($to["name"])) {
                throw new \InvalidArgumentException("The required request body field 'to.name' is missing.");
            }
            $scheduledDeparture = $this->validateJsonBodyField($request, "scheduledDeparture");            
            $flightType = FlightType::from($this->validateQueryParameter($request, "type"));

            return match ($flightType) {
                FlightType::Logged => $this->handleCreateLoggedFlight($request, $flight, $from, $to, $scheduledDeparture),                    
                FlightType::Scheduled, FlightType::Watched => $this->handleCreateScheduledOrWatchedFlight($request, $flightType,
                    $flight, $from, $to, $scheduledDeparture)
            };
        }

        private function handleCreateLoggedFlight(Request $request, string $flight, mixed $from, mixed $to, int $scheduledDeparture) : Flight {
            $aircraft = $this->validateJsonBodyNullableField($request, "aircraft");
            $registration = $this->validateJsonBodyNullableField($request, "registration");
            $actualDeparture = $this->validateJsonBodyNullableField($request, "actualDeparture");
            $scheduledArrival = $this->validateJsonBodyNullableField($request, "scheduledArrival");
            $actualArrival = $this->validateJsonBodyNullableField($request, "actualArrival");

            if ($aircraft !== NULL && $registration !== NULL && $actualDeparture !== NULL
                && $scheduledArrival !== NULL && $actualArrival !== NULL && isset($from["code"]) && isset($to["code"])
                && $this->isValidIataAirportCode($from["code"]) && $this->isValidIataAirportCode($to["code"])) {
                return $this->flightService->logFlight($flight, $from["name"], $from["code"], $to["name"], $to["code"],
                    $scheduledDeparture, $actualDeparture, $scheduledArrival, $actualArrival, $registration, $aircraft);
            }

            return $this->flightService->fetchAndLogFlight($flight, $from["name"], $to["name"], $scheduledDeparture);
        }

        private function handleCreateScheduledOrWatchedFlight(Request $request, FlightType $flightType, string $flight,
            mixed $from, mixed $to, int $scheduledDeparture) : Flight {
            $scheduledArrival = $this->validateJsonBodyField($request, "scheduledArrival");  
            return $this->flightService->createFlight($flightType, $flight, $from["name"], $to["name"],
                $scheduledDeparture, $scheduledArrival);
        }

        private function isValidIataAirportCode(?string $airportCode) {
            return is_string($airportCode) && preg_match('/^[A-Z]{3}$/', $airportCode);
        }
    }
?>