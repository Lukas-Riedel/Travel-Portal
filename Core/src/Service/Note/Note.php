<?php
    namespace Core\Service\Note;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Note",
        type: "object",
        description: "A class representing a note",
        required: ["id", "content", "timestamp"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the note",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "content",
                description: "The MD content of the note",
                type: "string",
                example: "**Lorem ipsum** dolor sit amet, consectetur adipiscing elit. Morbi fringilla sem sed nulla luctus iaculis. Cras rutrum turpis massa. Suspendisse."
            ),
            new OA\Property(
                property: "timestamp",
                description: "The creation time of the note in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            )
        ]
    )]
    class Note implements \JsonSerializable {        
        private ?string $id;
        private readonly string $content;
        private readonly int $timestamp;

        public function __construct(?string $id, string $content, int $timestamp) {
            $this->id = $id;
            $this->content = $content;
            $this->timestamp = $timestamp;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getContent() : string {
            return $this->content;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>