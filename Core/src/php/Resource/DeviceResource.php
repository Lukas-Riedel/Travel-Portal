<?php
    namespace Core\Resource;

    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Core\Service\Device\DeviceService;
    use Core\Service\Device\DeviceType;

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
                $group->get("", [$resource, "listDevices"]);
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
                    required: ["id", "type", "name", "token"],
                    properties: [
                        new OA\Property(
                            property: "id",
                            description: "The device-generated identifier of the device",
                            type: "string",
                            example: "8f3b0c9a-5cfa-4d47-bf5e-8e8f9f3a1a2b"
                        ),
                        new OA\Property(
                            property: "type",
                            ref: "#/components/schemas/DeviceType"
                        ),
                        new OA\Property(
                            property: "name",
                            description: "The name of the device",
                            type: "string",
                            example: "DESKTOP-PC"
                        ),
                        new OA\Property(
                            property: "data",
                            description: "The data of the device",
                            type: "object",
                            additionalProperties: true,
                            example: [
                                "fcmToken" => "fcm-1234567890"
                            ]
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The device was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Device")
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
        public function createDevice(Request $request, Response $response, array $routeArguments) : mixed {
            $deviceId = $this->validateJsonBodyField($request, "id");
            $deviceType = $this->validateJsonBodyField($request, "type");
            $deviceName = $this->validateJsonBodyField($request, "name");
            $deviceData = $this->validateJsonBodyNullableField($request, "data");
            $userId = $this->getUserInfo($request)->getUserId();

            $mappedType = DeviceType::from($deviceType);
            
            return $this->deviceService->registerOrUpdateDevice($deviceId, $mappedType, $deviceName, $deviceData, $userId);
        }

        #[OA\Get(
            path: "/devices",
            summary: "Retrieve a collection of devices",
            operationId: "listDevices",
            tags: ["Devices"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    description: "The type of the devices",
                    schema: new OA\Schema(ref: "#/components/schemas/DeviceType"),                    
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of devices.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Device")
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
                )
            ]
        )]
        public function listDevices(Request $request, Response $response, array $routeArguments) : mixed {
            $type = $this->validateQueryNullableParameter($request, "type");

            $mappedType = $type === null ? null : DeviceType::from($type);
            
            return $this->deviceService->getDevices($mappedType, null);
        }
    }