<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Core\Service\Expense\ExpenseService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Vouchers")]
    class VoucherResource extends AbstractResource {
        
        private readonly ExpenseService $expenseService;

        public function __construct(ExpenseService $expenseService) {
            $this->expenseService = $expenseService;
        }

        public static function register(App $app, ExpenseService $expenseService) : void {
            $resource = new self($expenseService);

            $app->group("/vouchers", function($group) use($resource) {
                $group->post("", [$resource, "createVoucher"]);
                $group->get("", [$resource, "listVouchers"]);
                $group->get("/{voucherId}", [$resource, "getVoucher"]);
                $group->patch("/{voucherId}", [$resource, "updateVoucher"]);
                $group->delete("/{voucherId}", [$resource, "removeVoucher"]);
            });
        }
        
        #[OA\Post(
            path: "/vouchers",
            summary: "Create a voucher",
            operationId: "createVoucher",
            tags: ["Vouchers"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: [ "code", "issuer", "value", "currency" ],
                    properties: [
                        new OA\Property(
                            property: "code",
                            type: "string",
                            description: "The code of the voucher",
                            example: "REB39M43HAMA"
                        ),
                        new OA\Property(
                            property: "issuer",
                            type: "string",
                            description: "The issuer of the voucher",
                            example: "FLIXBUS"
                        ),
                        new OA\Property(
                            property: "value",
                            description: "The value of the voucher in the specified currency",
                            type: "number",
                            format: "float",
                            example: 58
                        ),
                        new OA\Property(
                            property: "currency",
                            type: "string",
                            description: "The currency of the voucher",
                            example: "EUR"
                        ),
                        new OA\Property(
                            property: "expiration",
                            type: "integer",
                            description: "The expiration of the voucher in epoch seconds",
                            example: 1753912800
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The voucher was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Voucher")
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
        public function createVoucher(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $code = $this->requireJsonBodyField($request, "code");
            $issuer = $this->requireJsonBodyField($request, "issuer");
            $value = $this->requireJsonBodyField($request, "value");
            $currency = $this->requireJsonBodyField($request, "currency");
            $expiration = $this->getJsonBodyField($request, "expiration");

            return $this->expenseService->createVoucher($code, $issuer, $value, $currency, $expiration);
        }

        #[OA\Get(
            path: "/vouchers",
            summary: "Retrieve a collection of vouchers",
            operationId: "listVouchers",
            tags: ["Vouchers"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of vouchers.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Voucher")
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
        public function listVouchers(Request $request, Response $response, array $routeArguments) : mixed {  
            $this->requireAdmin($request);

            return $this->expenseService->getAllVouchers();
        }  

        #[OA\Get(
            path: "/vouchers/{voucherId}",
            summary: "Retrieve a voucher with the specified identifier",
            operationId: "getVoucher",
            tags: ["Vouchers"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "voucherId",
                    in: "path",
                    required: true,
                    description: "The identifier of the voucher",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a voucher with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Voucher")
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
        public function getVoucher(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $voucherId = $this->requirePathArgument($routeArguments, "voucherId");
            
            $voucher = $this->expenseService->getVoucher($voucherId);
            if ($voucher === null) {
                throw new NotFoundException($voucherId);
            }

            return $voucher;
        }

        #[OA\Patch(
            path: "/vouchers/{voucherId}",
            summary: "Update a voucher the specified identifier",
            operationId: "updateVoucher",
            tags: ["Vouchers"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "value",
                            description: "The value of the voucher in the specified currency",
                            type: "number",
                            format: "float",
                            example: 30
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "voucherId",
                    in: "path",
                    required: true,
                    description: "The identifier of the voucher",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Updated a voucher with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Voucher")
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
        public function updateVoucher(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);
            $wasUpdated = false;

            $voucherId = $this->requirePathArgument($routeArguments, "voucherId");
            
            $newValue = $this->getJsonBodyField($request, "value");
            if ($newValue !== null) {
                $wasUpdated |= $this->expenseService->updateVoucherValue($voucherId, $newValue);
            }

            $voucher = $this->expenseService->getVoucher($voucherId);
            if ($voucher === null) {
                throw new NotFoundException($voucherId);
            }

            return $voucher;
        }

        #[OA\Delete(
            path: "/vouchers/{voucherId}",
            summary: "Remove a voucher with the specified identifier",
            operationId: "removeVoucher",
            tags: ["Vouchers"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "voucherId",
                    in: "path",
                    required: true,
                    description: "The identifier of the voucher",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a voucher with the specified identifier."
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
        public function removeVoucher(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $voucherId = $this->requirePathArgument($routeArguments, "voucherId");
            
            $wasRemoved = $this->expenseService->removeVoucher($voucherId);
            if (!$wasRemoved) {
                throw new NotFoundException($voucherId);
            }

            return null;
        }
    }
?>