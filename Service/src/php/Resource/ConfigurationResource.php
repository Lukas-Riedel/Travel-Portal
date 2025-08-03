<?php
    namespace Service\Resource;

    use OpenApi\Attributes\AdditionalProperties;
    use Service\Service\Configuration\ConfigurationService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Service\Routing\NotFoundException;
use Service\Routing\NotUpdatedException;

    #[OA\Tag(name: "Configuration")]
    class ConfigurationResource extends AbstractResource {

        private readonly ConfigurationService $configurationService;

        public function __construct(ConfigurationService $configurationService) {
            $this->configurationService = $configurationService;
        }

        public static function register(App $app, ConfigurationService $configurationService) : void {
            $resource = new self($configurationService);

            $app->group("/configuration", function($group) use($resource) {
                $group->get("", [$resource, "listConfiguration"]);
                $group->put("/{configurationKey}", [$resource, "replaceConfiguration"]);
            });
        }

        #[OA\Get(
            path: "/configuration",
            summary: "Retrieve a collection of configuration entries",
            operationId: "listConfiguration",
            tags: ["Configuration"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of configuration entries.",
                    content: new OA\JsonContent(
                        type: "object",
                        additionalProperties: new AdditionalProperties(
                            oneOf: [
                                new OA\Schema(type: "array", items: new OA\Items()),
                                new OA\Schema(type: "object")
                            ]
                        )
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
        public function listConfiguration(Request $request, Response $response) : mixed {
            $allowPrivate = $this->getAccessToken($request)->isAdmin();
            return $this->configurationService->getAllConfigurationEntries($allowPrivate);
        }

        #[OA\Put(
            path: "/configuration/{configurationKey}",
            summary: "Replace a configuration entry with the specified key",
            operationId: "replaceConfiguration",
            tags: ["Configuration"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "configurationKey",
                    in: "path",
                    required: true,
                    description: "The key of the configuration entry",
                    schema: new OA\Schema(type: "string"),
                    example: "expensify",
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    additionalProperties: new AdditionalProperties(
                        oneOf: [
                            new OA\Schema(type: "array", items: new OA\Items()),
                            new OA\Schema(type: "object")
                        ]
                    ),
                    example: [
                        "mainCurrencyValue" => "EUR"
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Returned a replace configuration entry with the specified key.",
                    content: new OA\JsonContent(
                        type: "object",
                        additionalProperties: new AdditionalProperties(
                            oneOf: [
                                new OA\Schema(type: "array", items: new OA\Items()),
                                new OA\Schema(type: "object")
                            ]
                        )
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
        public function replaceConfiguration(Request $request, Response $response, array $routeArguments) : mixed {
            $this->validateAdminPermissions($request);

            $configurationKey = $this->validateArgumentKey($routeArguments, "configurationKey");
            $newValue = $this->validateJsonBody($request);
            
            $wasUpdated = $this->configurationService->updateConfigurationEntry($configurationKey, $newValue);
            if (!$wasUpdated) {
                throw new NotUpdatedException($configurationKey);
            }

            $configurationEntry = $this->configurationService->getConfigurationEntry($configurationKey);
            if ($configurationEntry === NULL) {
                throw new NotFoundException($configurationKey);
            }

            return $configurationEntry;
        }
    }
?>