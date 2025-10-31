<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Common\Routing\NotFoundException;
    use Core\Service\Document\DocumentService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;

    #[OA\Tag(name: "Documents")]
    class DocumentResource extends AbstractResource {
        
        private readonly DocumentService $documentService;

        public function __construct(DocumentService $documentService) {
            $this->documentService = $documentService;
        }

        public static function register(App $app, DocumentService $documentService) : void {
            $resource = new self($documentService);

            $app->group("/documents", function($group) use($resource) {
                $group->post("", [$resource, "createDocument"]);
                $group->get("", [$resource, "listDocuments"]);
                $group->get("/{documentId}", [$resource, "getDocument"]);
                $group->delete("/{documentId}", [$resource, "removeDocument"]);
            });
        }
        
        #[OA\Post(
            path: "/documents",
            summary: "Create a document",
            operationId: "createDocument",
            tags: ["Documents"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: [ "name", "documentId", "issuer" ],
                    properties: [
                        new OA\Property(
                            property: "name",
                            type: "string",
                            description: "The name of the document",
                            example: "EEA ID Card"
                        ),
                        new OA\Property(
                            property: "documentId",
                            type: "string",
                            description: "The identifier of the document",
                            example: "203432977"
                        ),
                        new OA\Property(
                            property: "issuer",
                            type: "string",
                            description: "The issuer of the document",
                            example: "Prague 4"
                        ),
                        new OA\Property(
                            property: "expiration",
                            type: "integer",
                            description: "The expiration of the document in epoch seconds",
                            example: 1753912800
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The document was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Document")
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
        public function createDocument(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $name = $this->requireJsonBodyField($request, "name");
            $documentId = $this->requireJsonBodyField($request, "documentId");
            $issuer = $this->requireJsonBodyField($request, "issuer");
            $expiration = $this->getJsonBodyField($request, "expiration");

            return $this->documentService->createDocument($name, $documentId, $issuer, $expiration);
        }

        #[OA\Get(
            path: "/documents",
            summary: "Retrieve a collection of documents",
            operationId: "listDocuments",
            tags: ["Documents"],
            security: [ ["bearerAuth" => []] ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of documents.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Document")
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
        public function listDocuments(Request $request, Response $response, array $routeArguments) : mixed {         
            $this->requireAdmin($request);

            return $this->documentService->getAllDocuments();
        }  

        #[OA\Get(
            path: "/documents/{documentId}",
            summary: "Retrieve a document with the specified identifier",
            operationId: "getDocument",
            tags: ["Documents"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "documentId",
                    in: "path",
                    required: true,
                    description: "The identifier of the document",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a document with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Document")
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
        public function getDocument(Request $request, Response $response, array $routeArguments) : mixed {  
            $this->requireAdmin($request);

            $documentId = $this->requirePathArgument($routeArguments, "documentId");
            
            $document = $this->documentService->getDocument($documentId);
            if ($document === null) {
                throw new NotFoundException($documentId);
            }

            return $document;
        }

        #[OA\Delete(
            path: "/documents/{documentId}",
            summary: "Remove a document with the specified identifier",
            operationId: "removeDocument",
            tags: ["Documents"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "documentId",
                    in: "path",
                    required: true,
                    description: "The identifier of the document",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a document with the specified identifier."
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
        public function removeDocument(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $documentId = $this->requirePathArgument($routeArguments, "documentId");
            
            $wasRemoved = $this->documentService->removeDocument($documentId);
            if (!$wasRemoved) {
                throw new NotFoundException($documentId);
            }

            return null;
        }
    }
?>