<?php
    namespace Service\Resource;

    use OpenApi\Attributes\AdditionalProperties;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;

    use OpenApi\Attributes as OA;
    use Service\Service\Device\DeviceService;
    use Service\Service\Device\DeviceType;

    #[OA\Tag(name: "Devices")]
    class DeviceResource extends AbstractResource {

        private readonly DeviceService $deviceService;

        public function __construct(DeviceService $deviceService) {
            $this->deviceService = $deviceService;
        }

        public static function register(App $app, DeviceService $deviceService) : void {
            $resource = new self($deviceService);

            $app->group("/devices", function($group) use($resource) {
                $group->post("", [$resource, "createDevice"]);
            });
        }
        
        #[OA\Post(
            path: "/devices",
            summary: "Create a device",
            operationId: "createDevice",
            tags: ["Devices"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["type", "token"],
                    properties: [
                        new OA\Property(
                            property: "type",
                            ref: "#/components/schemas/DeviceType"
                        ),
                        new OA\Property(
                            property: "token",
                            type: "string",
                            description: "Device token",
                            example: "devjFpQfdQ32P6cG0X6DrY:APA9332t1acBH11y41gABcDiMuK2HsEOzDbI5Mh1vGBn-1Da6TggFUQb28KlIWDHRAFDCmmhFv7XHDvWTZFihX6bOCDcUQCzIFxa9vFGKKcVJsc"
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. The device was created."
                ),
                new OA\Response(
                    response: 400,
                    description: "Bad Request. The request has invalid syntax or cannot be fulfilled.",
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
        public function createDevice(Request $request, Response $response, array $routeArguments) : mixed {
            $type = DeviceType::from($this->validateJsonBodyField($request, "type"));
            $token = $this->validateJsonBodyField($request, "token");
            $userId = $this->getAccessToken($request)->getUserId();
            
            $this->deviceService->registerOrUpdateDevice($type, $token, $userId);
            return NULL;
        }
    }