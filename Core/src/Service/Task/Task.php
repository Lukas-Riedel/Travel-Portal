<?php
    namespace Core\Service\Task;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Task",
        type: "object",
        description: "A class representing a task",
        required: ["description", "priority", "actionable"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the task",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "title",
                description: "The title of the task",
                type: "string",
                example: "Upcoming flight"
            ),
            new OA\Property(
                property: "description",
                description: "The description of the task",
                type: "string",
                example: "Complete the project documentation"
            ),
            new OA\Property(
                property: "priority",
                description: "The priority of the task",
                ref: "#/components/schemas/TaskPriority"
            ),
            new OA\Property(
                property: "deadline",
                description: "The deadline for the task in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            ),
            new OA\Property(
                property: "notificationInterval",
                description: "The interval to repeat deadline notifications in seconds",
                type: "integer",
                format: "int64",
                example: 86400
            ),
            new OA\Property(
                property: "url",
                description: "An external URL associated with the task",
                type: "string",
                example: "https://www.flightradar24.com/data/flights/EK139"
            ),
            new OA\Property(
                property: "actionable",
                description: "Whether the task can be acted upon (e.g. edited or removed)",
                type: "boolean",
                example: true
            )
        ]
    )]
    class Task implements \JsonSerializable {
           
        private ?string $id;
        private readonly ?string $title;
        private readonly string $description;
        private readonly TaskPriority $priority;
        private readonly ?int $deadline;
        private readonly ?int $notificationInterval;
        private readonly ?string $url;
        private readonly bool $actionable;

        public function __construct(?string $id, ?string $title, string $description, TaskPriority $priority, ?int $deadline, ?int $notificationInterval, ?string $url, bool $actionable) {
            $this->id = $id;
            $this->title = $title;
            $this->description = $description;
            $this->priority = $priority;
            $this->deadline = $deadline;
            $this->notificationInterval = $notificationInterval;
            $this->url = $url;
            $this->actionable = $actionable;
        }

        public function getId() : ?string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getTitle() : ?string {
            return $this->title;
        }

        public function getDescription() : string {
            return $this->description;
        }

        public function getPriority() : TaskPriority {
            return $this->priority;
        }

        public function getDeadline() : ?int {
            return $this->deadline;
        }

        public function getNotificationInterval() : ?int {
            return $this->notificationInterval;
        }

        public function getUrl() : ?string {
            return $this->url;
        }

        public function isActionable() : bool {
            return $this->actionable;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
