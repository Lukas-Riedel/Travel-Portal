<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    use OpenApi\Attributes as OA;
    use Common\Routing\NotFoundException;
    use Core\Service\Label\LabelService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Note\NoteService;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Place\Place;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Place\PlaceType;
    use Core\Service\Place\SpecialPlaceType;
    use Monolog\Logger;

    #[OA\Tag(name: "Places")]
    class PlaceResource extends AbstractResource {

        private readonly PlaceService $placeService;
        private readonly PhotoService $photoService;
        private readonly LabelService $labelService;
        private readonly NoteService $noteService;
        private readonly HighlightService $highlightService;
        private readonly Logger $logger;

        public function __construct(PlaceService $placeService, PhotoService $photoService, LabelService $labelService, NoteService $noteService, HighlightService $highlightService, Logger $logger) {
            $this->placeService = $placeService;
            $this->photoService = $photoService;
            $this->labelService = $labelService;
            $this->noteService = $noteService;
            $this->highlightService = $highlightService;
            $this->logger = $logger;
        }

        public static function register(App $app, PlaceService $placeService, PhotoService $photoService, LabelService $labelService, NoteService $noteService, HighlightService $highlightService, Logger $logger) : void {
            $resource = new self($placeService, $photoService, $labelService, $noteService, $highlightService, $logger);

            $app->group("/places", function($group) use($resource) {
                $group->post("", [$resource, "createPlace"]);
                $group->get("", [$resource, "listPlaces"]);
                $group->get("/{placeId}", [$resource, "getPlace"]);
                $group->patch("/{placeId}", [$resource, "updatePlace"]);
                $group->delete("/{placeId}", [$resource, "removePlace"]);
                $group->post("/{placeId}/labels", [$resource, "createPlaceLabel"]);
                $group->delete("/{placeId}/labels/{labelId}", [$resource, "removePlaceLabel"]);
                $group->post("/{placeId}/notes", [$resource, "createPlaceNote"]);
                $group->patch("/{placeId}/notes/{noteId}", [$resource, "updatePlaceNote"]);
                $group->delete("/{placeId}/notes/{noteId}", [$resource, "removePlaceNote"]);
                $group->post("/{placeId}/highlights", [$resource, "createPlaceHighlight"]);
                $group->delete("/{placeId}/highlights/{highlightId}", [$resource, "removePlaceHighlight"]);
                $group->post("/{placeId}/albums", [$resource, "createPlaceAlbum"]);
                $group->patch("/{placeId}/albums/{albumId}", [$resource, "updatePlaceAlbum"]);
                $group->post("/{placeId}/albums/{albumId}/refresh", [$resource, "refreshPlaceAlbum"]);
                $group->post("/{placeId}/albums/{albumId}/photos", [$resource, "createPlaceAlbumPhoto"]);
                $group->get("/{placeId}/albums/{albumId}/photos", [$resource, "listPlaceAlbumPhotos"]);
            });
        }
        
        #[OA\Post(
            path: "/places",
            summary: "Create a place",
            operationId: "createPlace",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "address",
                    in: "query",
                    required: true,
                    description: "The address of the place",
                    example: "Vodičkova 25, 110 00 Prague 1, Czech Republic",
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    required: true,
                    description: "The type of the place",
                    schema: new OA\Schema(ref: "#/components/schemas/SpecialPlaceType")                    
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["name"],
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the place",
                            type: "string",
                            example: "Prague"
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. The place was created.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Place")
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
        public function createPlace(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $name = $this->requireJsonBodyField($request, "name");
            $address = $this->requireQueryParameter($request, "address");
            $type = $this->requireQueryParameter($request, "type");

            $mappedType = SpecialPlaceType::from($type);
            
            return match ($mappedType) {
                SpecialPlaceType::Permanent => $this->placeService->createPermanentPlace($name, $address),
                SpecialPlaceType::Candidate => $this->placeService->createCandidatePlace($name, $address)
            };
        }

        #[OA\Get(
            path: "/places",
            summary: "Retrieve a collection of places",
            operationId: "listPlaces",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "year",
                    in: "query",
                    description: "The year of the places",
                    example: "2025"
                ),
                new OA\Parameter(
                    name: "tripId",
                    in: "query",
                    description: "The identifier of the trip of the places",
                    example: "ed94e7ac-a1f7-41ba-b2bf-6e211504037c"
                ),
                new OA\Parameter(
                    name: "categoryId",
                    in: "query",
                    description: "The identifier of the category of the places",
                    example: "9ca296dd-55ac-4015-8ed3-5003402f1c74"
                ),
                new OA\Parameter(
                    name: "labelId",
                    in: "query",
                    description: "The identifier of the label of the places",
                    example: "ceb8f307-b2e5-4b1b-810c-eaf270e547a0"
                ),
                new OA\Parameter(
                    name: "albumId",
                    in: "query",
                    description: "The identifier of the album of the places",
                    example: "5d7bae2e-6530-403a-a668-bdbcbe5d8a81"
                ),
                new OA\Parameter(
                    name: "photoId",
                    in: "query",
                    description: "The identifier of the photo of the places",
                    example: "3f3afd99-f667-4cb4-aef1-b9d26d318a54"
                ),
                new OA\Parameter(
                    name: "minStart",
                    in: "query",
                    description: "The minimum start date of the places in epoch seconds",
                    example: 1689786000
                ),
                new OA\Parameter(
                    name: "maxEnd",
                    in: "query",
                    description: "The maximum end date of the places in epoch seconds",
                    example: 1689786000
                ),
                new OA\Parameter(
                    name: "maxQuality",
                    in: "query",
                    description: "The maximum quality of the places",
                    example: 80
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    description: "The type of the place",
                    schema: new OA\Schema(ref: "#/components/schemas/PlaceType")                    
                ),
                new OA\Parameter(
                    name: "nearbyPlaces",
                    in: "query",
                    description: "The count of nearby places for each returned place",
                    example: 3
                ),
                new OA\Parameter(
                    name: "limit",
                    in: "query",
                    description: "The limit of returned places",
                    example: 50
                ),
                new OA\Parameter(
                    name: "include",
                    in: "query",
                    description: "The comma-separated list of included entities",
                    example: "highlights,statistics"
                ),
                new OA\Parameter(
                    name: "sort",
                    in: "query",
                    description: "The sorting strategy of the places",
                    schema: new OA\Schema(ref: "#/components/schemas/PlaceSortingStrategy"),                    
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of places.",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Place")
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
        public function listPlaces(Request $request, Response $response, array $routeArguments) : mixed {
            $year = $this->getQueryParameter($request, "year");
            $tripId = $this->getQueryParameter($request, "tripId");
            $categoryId = $this->getQueryParameter($request, "categoryId");
            $labelId = $this->getQueryParameter($request, "labelId");
            $albumId = $this->getQueryParameter($request, "albumId");
            $photoId = $this->getQueryParameter($request, "photoId");
            $minStart = $this->getQueryParameter($request, "minStart");
            $maxEnd = $this->getQueryParameter($request, "maxEnd");
            $maxQuality = $this->getQueryParameter($request, "maxQuality");
            $type = $this->getQueryParameter($request, "type") ?? PlaceType::Regular->value;
            $nearbyPlaces = $this->getQueryParameter($request, "nearbyPlaces");
            $limit = $this->getQueryParameter($request, "limit");
            $include = $this->getQueryParameter($request, "include") ?? "";
            $sort = $this->getQueryParameter($request, "sort") ?? PlaceSortingStrategy::OldestAscending->value;

            // TODO: Do not use the backing value, refactor the service code first.
            $mappedInclude = array_map(fn($entity) => PlaceIncludedEntity::from($entity)->value, 
                array_filter(explode(",", $include)));
            $mappedSort = PlaceSortingStrategy::from($sort);
            $mappedType = PlaceType::from($type);
            
            return match ($mappedType) {
                PlaceType::Regular => $this->placeService->getRegularPlaces($categoryId, $labelId, $tripId, $year, $albumId, $photoId, $maxQuality, $minStart, $maxEnd, $nearbyPlaces, $limit, $mappedInclude, $mappedSort),
                PlaceType::Candidate => $this->placeService->getCandidatePlaces($categoryId, $tripId, $labelId, $nearbyPlaces, $mappedInclude)
            };
        }

        #[OA\Get(
            path: "/places/{placeId}",
            summary: "Retrieve a place with the specified identifier",
            operationId: "getPlace",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "nearbyPlaces",
                    in: "query",
                    description: "The count of nearby places for the returned place",
                    example: 3
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Place")
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
        public function getPlace(Request $request, Response $response, array $routeArguments) : mixed {    
            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $nearbyPlaces = $this->getQueryParameter($request, "nearbyPlaces");

            return $this->doGetPlace($placeId, $nearbyPlaces);
        }
        
        #[OA\Patch(
            path: "/places/{placeId}",
            summary: "Update a place with the specified identifier",
            operationId: "updatePlace",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the place",
                            type: "string",
                            example: "Prague"
                        ),
                        new OA\Property(
                            property: "latitude",
                            type: "number",
                            format: "float",
                            description: "The latitude of the place",
                            example: 50.0755
                        ),
                        new OA\Property(
                            property: "longitude",
                            type: "number",
                            format: "float",
                            description: "The longitude of the place",
                            example: 14.4378
                        ),
                        new OA\Property(
                            property: "excerpt",
                            type: "string",
                            description: "The excerpt of the place",
                            example: "Prague, the capital of the Czech Republic, is a city where medieval charm meets vibrant modern life. Known as the \"City of a Hundred Spires,\" it dazzles with its Gothic cathedrals, baroque palaces, and the fairytale-like Prague Castle towering above the Vltava River. Strolling across the historic Charles Bridge or wandering the cobblestone lanes of the Old Town, visitors find a mix of history, art, and lively cafés. With its rich culture and timeless beauty, Prague feels both grand and intimate, a city that leaves a lasting impression."
                        ),
                        new OA\Property(
                            property: "mainHighlight",
                            description: "The main highlight of the place",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the main highlight of the place",
                                    type: "string",
                                    example: "f93c6a37-9151-4747-af7f-30eac920216e"
                                )
                            ]
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Place")
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
        public function updatePlace(Request $request, Response $response, array $routeArguments) : mixed {           
            $this->requireAdmin($request);
            $wasUpdated = false;

            $placeId = $this->requirePathArgument($routeArguments, "placeId");

            $newName = $this->getJsonBodyField($request, "name");
            if ($newName !== null) {
                $wasUpdated |= $this->placeService->updatePlaceName($placeId, $newName);
            }

            $newLatitude = $this->getJsonBodyField($request, "latitude");
            $newLongitude = $this->getJsonBodyField($request, "longitude");
            if ($newLatitude !== null && $newLatitude !== null) {
                $wasUpdated |= $this->placeService->updatePlaceLocation($placeId, $newLatitude, $newLongitude);
            }

            if ($this->existsJsonBodyField($request, "excerpt")) {
                $newExcerpt = $this->getJsonBodyField($request, "newExcerpt");
                $wasUpdated |= $this->placeService->updatePlaceExcerpt($placeId, $newExcerpt);
            }                 
            
            $newMainHighlight = $this->getJsonBodyField($request, "mainHighlight");
            if ($newMainHighlight !== null && isset($newMainHighlight["id"])) {
                $wasUpdated |= $this->placeService->updatePlaceMainHighlight($placeId, $newMainHighlight["id"]);
            }      
            
            if (!$wasUpdated) {
                $this->logger->warning("The place with the identifier '{$placeId}' was not updated.");
            }

            return $this->doGetPlace($placeId);
        }

        #[OA\Delete(
            path: "/places/{placeId}",
            summary: "Remove a place with the specified identifier",
            operationId: "removePlace",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    required: true,
                    description: "The type of the place",
                    schema: new OA\Schema(ref: "#/components/schemas/SpecialPlaceType")                    
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a place with the specified identifier."
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
        public function removePlace(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $type = $this->requireQueryParameter($request, "type");
                        
            $mappedType = SpecialPlaceType::from($type);

            $wasRemoved = match ($mappedType) {
                SpecialPlaceType::Permanent => $this->placeService->removePermanentPlace($placeId),
                SpecialPlaceType::Candidate => $this->placeService->removeCandidatePlace($placeId)
            };
            
            if (!$wasRemoved) {
                throw new NotFoundException($placeId);
            }

            return null;
        }  

        #[OA\Post(
            path: "/places/{placeId}/notes",
            summary: "Create a note for a place with the specified identifier",
            operationId: "createPlaceNote",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["content"],
                    properties: [
                        new OA\Property(
                            property: "content",
                            description: "The MD content of the note",
                            type: "string",
                            example: "**Lorem ipsum** dolor sit amet, consectetur adipiscing elit. Morbi fringilla sem sed nulla luctus iaculis. Cras rutrum turpis massa. Suspendisse."
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a note for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Note")
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
        public function createPlaceNote(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $content = $this->requireJsonBodyField($request, "content");

            return $this->noteService->createPlaceNote($placeId, $content);
        }

        #[OA\Patch(
            path: "/places/{placeId}/notes/{noteId}",
            summary: "Update a note for a place with the specified identifier",
            operationId: "updatePlaceNote",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "content",
                            description: "The MD content of the note",
                            type: "string",
                            example: "**Lorem ipsum** dolor sit amet, consectetur adipiscing elit. Morbi fringilla sem sed nulla luctus iaculis. Cras rutrum turpis massa. Suspendisse."
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "noteId",
                    in: "path",
                    required: true,
                    description: "The identifier of the note",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated a note for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Place")
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
        public function updatePlaceNote(Request $request, Response $response, array $routeArguments) : mixed {           
            $this->requireAdmin($request);
            $wasUpdated = false;

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $noteId = $this->requirePathArgument($routeArguments, "noteId");

            $newContent = $this->getJsonBodyField($request, "content");
            if ($newContent !== null) {
                $wasUpdated |= $this->noteService->updateNoteContent($noteId, $newContent);
            }     
            
            if (!$wasUpdated) {
                $this->logger->warning("The note with the identifier '{$noteId}' was not updated.");
            }

            return $this->noteService->getPlaceNote($placeId, $noteId);
        }

        #[OA\Delete(
            path: "/places/{placeId}/notes/{noteId}",
            summary: "Remove a note for a place with the specified identifier",
            operationId: "removePlaceNote",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "noteId",
                    in: "path",
                    required: true,
                    description: "The identifier of the note",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a note for a place with the specified identifier."
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
        public function removePlaceNote(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $noteId = $this->requirePathArgument($routeArguments, "noteId");

            $wasRemoved = $this->noteService->removePlaceNote($placeId, $noteId);
            if (!$wasRemoved) {
                throw new NotFoundException($noteId);                
            }

            return null;
        }

        #[OA\Post(
            path: "/places/{placeId}/highlights",
            summary: "Create a highlight for a place with the specified identifier",
            operationId: "createPlaceHighlight",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["photo"],
                    properties: [
                        new OA\Property(
                            property: "photo",
                            description: "The photo representing the highlight",
                            type: "object",
                            required: ["id"],
                            properties: [
                                new OA\Property(
                                    property: "id",
                                    description: "The identifier of the photo representing the highlight",
                                    type: "string",
                                    example: "f93c6a37-9151-4747-af7f-30eac920216e"
                                )
                            ]
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a highlight for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Highlight")
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
        public function createPlaceHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $photo = $this->requireJsonBodyField($request, "photo");
            if (!is_array($photo) || !isset($photo["id"])) {
                throw new \InvalidArgumentException("The required request body field 'photo.id' is missing.");
            }

            return $this->highlightService->createPlaceHighlight($placeId, $photo["id"]);
        }

        #[OA\Delete(
            path: "/places/{placeId}/highlights/{highlightId}",
            summary: "Remove a highlight for a place with the specified identifier",
            operationId: "removePlaceHighlight",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "highlightId",
                    in: "path",
                    required: true,
                    description: "The identifier of the highlight",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a highlight for a place with the specified identifier."
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
        public function removePlaceHighlight(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $highlightId = $this->requirePathArgument($routeArguments, "highlightId");

            $wasRemoved = $this->highlightService->removePlaceHighlight($placeId, $highlightId);
            if (!$wasRemoved) {
                throw new NotFoundException($highlightId);                
            }

            return null;
        }

        #[OA\Post(
            path: "/places/{placeId}/labels",
            summary: "Create a label for a place with the specified identifier",
            operationId: "createPlaceLabel",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["name"],
                    properties: [
                        new OA\Property(
                            property: "name",
                            description: "The name of the label",
                            type: "string",
                            example: "City"
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created a label for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Label")
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
        public function createPlaceLabel(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $name = $this->requireJsonBodyField($request, "name");

            return $this->labelService->createLabel($placeId, $name);
        }

        #[OA\Delete(
            path: "/places/{placeId}/labels/{labelId}",
            summary: "Remove a label for a place with the specified identifier",
            operationId: "removePlaceLabel",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "labelId",
                    in: "path",
                    required: true,
                    description: "The identifier of the label",
                    schema: new OA\Schema(type: "string"),
                    example: "6846808f-b8d8-409c-bc78-97878b3a4446",
                )
            ],
            responses: [
                new OA\Response(
                    response: 204,
                    description: "Success. Removed a label for a place with the specified identifier."
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
        public function removePlaceLabel(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $labelId = $this->requirePathArgument($routeArguments, "labelId");

            $wasRemoved = $this->labelService->removeLabelForPlace($placeId, $labelId);
            if (!$wasRemoved) {
                throw new NotFoundException($labelId);                
            }

            return null;
        }

        #[OA\Post(
            path: "/places/{placeId}/albums",
            summary: "Create an album for a place with the specified identifier",
            operationId: "createPlaceAlbum",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "timestamp",
                    in: "query",
                    required: true,
                    description: "The timestamp of the album in epoch seconds",
                    schema: new OA\Schema(type: "integer"),
                    example: 1755714600
                )
            ],
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Created an album for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Album")
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
        public function createPlaceAlbum(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $timestamp = $this->requireQueryParameter($request, "timestamp");

            $place = $this->doGetPlace($placeId);

            return $this->photoService->createAlbum($place->getPlaceIdentifier(), $timestamp);
        }

        #[OA\Patch(
            path: "/places/{placeId}/albums/{albumId}",
            summary: "Update an album for a place with the specified identifier",
            operationId: "updatePlaceAlbum",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "reviewed",
                            type: "boolean",
                            description: "Whether the album has been reviewed or not",
                            example: true
                        )
                    ]
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "albumId",
                    in: "path",
                    required: true,
                    description: "The identifier of the album",
                    schema: new OA\Schema(type: "string"),
                    example: "95d623f3-5891-4de6-ba8b-fe3d8ba45e8d",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Updated an album for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Album")
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
        public function updatePlaceAlbum(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);
            $wasUpdated = false;

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $albumId = $this->requirePathArgument($routeArguments, "albumId");
            
            $place = $this->doGetPlace($placeId);
            $album = $place->findAlbum($albumId);

            if ($album === null) {
                throw new NotFoundException($albumId);
            }

            $reviewed = $this->getJsonBodyField($request, "reviewed");
            if ($reviewed !== null) {
                if (boolval($reviewed) === true) {
                    $wasUpdated |= $this->photoService->updateAlbumReviewed($albumId);
                }
                else {
                    throw new \InvalidArgumentException("The request body field 'reviewed' cannot be 'false'.");
                }
            }
            
            if (!$wasUpdated) {
                $this->logger->warning("The album with the identifier '{$albumId}' was not updated.");
            }
            
            
            $place = $this->doGetPlace($placeId);
            return $place->findAlbum($albumId);
        }

        #[OA\Post(
            path: "/places/{placeId}/albums/{albumId}/refresh",
            summary: "Refresh an album for a place with the specified identifier",
            operationId: "refreshPlaceAlbum",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "albumId",
                    in: "path",
                    required: true,
                    description: "The identifier of the album",
                    schema: new OA\Schema(type: "string"),
                    example: "95d623f3-5891-4de6-ba8b-fe3d8ba45e8d",
                ),
                new OA\Parameter(
                    name: "mainPhotoPosition",
                    in: "query",
                    description: "The position of the main photo of the album",
                    schema: new OA\Schema(type: "integer"),
                    example: 15,
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Refreshed an album for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Album")
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
        public function refreshPlaceAlbum(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $albumId = $this->requirePathArgument($routeArguments, "albumId");
            $mainPhotoPosition = $this->getQueryParameter($request, "mainPhotoPosition");

            $place = $this->doGetPlace($placeId);
            $album = $place->findAlbum($albumId);

            if ($album === null) {
                throw new NotFoundException($albumId);
            }

            $this->photoService->updateAlbum($albumId, $place->getLatitude(), $place->getLongitude(), $mainPhotoPosition);
            
            $place = $this->doGetPlace($placeId);
            return $place->findAlbum($albumId);
        }

        #[OA\Post(
            path: "/places/{placeId}/albums/{albumId}/photos",
            summary: "Create a photo for an album for a place with the specified identifier",
            operationId: "createPlaceAlbumPhoto",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "albumId",
                    in: "path",
                    required: true,
                    description: "The identifier of the album",
                    schema: new OA\Schema(type: "string"),
                    example: "95d623f3-5891-4de6-ba8b-fe3d8ba45e8d",
                )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    required: ["fileName", "data"],
                    properties: [
                        new OA\Property(
                            property: "fileName",
                            type: "string",
                            description: "The original name of the uploaded file",
                            example: "DSC_0001.jpg"
                        ),
                        new OA\Property(
                            property: "batchId",
                            type: "string",
                            description: "The identifier for the current upload batch",
                            example: "39e5816f-8d47-4545-ac1b-c42bcf6a3f13"
                        ),
                        new OA\Property(
                            property: "expectedBatchSize",
                            type: "integer",
                            description: "The total number of photos expected in the batch",
                            example: 10
                        ),
                        new OA\Property(
                            property: "batchPosition",
                            type: "integer",
                            description: "The zero-based index of the photo within the batch",
                            example: 3
                        ),
                        new OA\Property(
                            property: "replacedPhotoId",
                            type: "string",
                            description: "The ID of the photo being replaced",
                            example: "87122d18-1ab1-4d2a-8340-858b94dbb76e"
                        ),
                        new OA\Property(
                            property: "data",
                            type: "string",
                            description: "The Base64-encoded data representing the photo",
                            example: "kMuvQKDPessd0F9iA92L4zYs9kXWD16RArp3IbMxTaTdW8avf6ajPwTeUvpRSibB"
                        )
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Success. Create a photo for an album for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/PendingPhoto")
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
        public function createPlaceAlbumPhoto(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $albumId = $this->requirePathArgument($routeArguments, "albumId");

            $fileName = $this->requireJsonBodyField($request, "fileName");
            $data = $this->requireJsonBodyField($request, "data");
            $batchId = $this->getJsonBodyField($request, "batchId");
            $expectedBatchSize = $this->getJsonBodyField($request, "expectedBatchSize");
            $batchPosition = $this->getJsonBodyField($request, "batchPosition");
            $replacedPhotoId = $this->getJsonBodyField($request, "replacedPhotoId");
            
            $place = $this->doGetPlace($placeId);
            $album = $place->findAlbum($albumId);

            if ($album === null) {
                throw new NotFoundException($albumId);
            }

            if ($batchId !== null && $expectedBatchSize !== null && $batchPosition !== null) {
                return $this->photoService->uploadPhoto($fileName, $albumId, $batchId, $expectedBatchSize, $batchPosition, $data);
            }
            if ($replacedPhotoId !== null) {
                return $this->photoService->replacePhoto($fileName, $albumId, $replacedPhotoId, $data);
            }

            throw new \InvalidArgumentException("The request body fields combination is incorrect.");
        }

        #[OA\Get(
            path: "/places/{placeId}/albums/{albumId}/photos",
            summary: "Retrieve a collection of photos for an album for a place with the specified identifier",
            operationId: "listPlaceAlbumPhotos",
            tags: ["Places"],
            security: [ ["bearerAuth" => []] ],
            parameters: [
                new OA\Parameter(
                    name: "placeId",
                    in: "path",
                    required: true,
                    description: "The identifier of the place",
                    schema: new OA\Schema(type: "string"),
                    example: "80e193aa-8d74-4ff6-af1a-91cc2d6cef8a",
                ),
                new OA\Parameter(
                    name: "albumId",
                    in: "path",
                    required: true,
                    description: "The identifier of the album",
                    schema: new OA\Schema(type: "string"),
                    example: "95d623f3-5891-4de6-ba8b-fe3d8ba45e8d",
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Success. Retrieved a collection of photos for an album for a place with the specified identifier.",
                    content: new OA\JsonContent(ref: "#/components/schemas/Photo")
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
        public function listPlaceAlbumPhotos(Request $request, Response $response, array $routeArguments) : mixed {
            $placeId = $this->requirePathArgument($routeArguments, "placeId");
            $albumId = $this->requirePathArgument($routeArguments, "albumId");
            
            $place = $this->doGetPlace($placeId);
            $album = $place->findAlbum($albumId);

            if ($album === null) {
                throw new NotFoundException($albumId);
            }

            return $this->photoService->getPhotosForAlbum($albumId, $place->getLatitude(), $place->getLongitude());
        }

        private function doGetPlace(string $placeId, ?int $nearbyPlaces = null) : Place {            
            $place = $this->placeService->getRegularPlace($placeId, $nearbyPlaces);
            if ($place !== null) {
                return $place;
            }
            
            $place = $this->placeService->getCandidatePlace($placeId, $nearbyPlaces);
            if ($place !== null) {
                return $place;
            }

            throw new NotFoundException($placeId);
        }
    }
?>