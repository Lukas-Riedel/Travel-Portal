<?php
    namespace Core\Service\Task;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TaskPriority",
        type: "string",
        description: "The priority of the task"
    )]
    enum TaskPriority : string {
        case Lowest = "lowest";
        case Low = "low";
        case Medium = "medium";
        case High = "high";
        case Highest = "highest";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }

        public function toNumber() : int {
            return match($this) {
                self::Lowest => 0,
                self::Low => 1,
                self::Medium => 2,
                self::High => 3,
                self::Highest => 4
            };
        }

        public static function fromNumber(int $number) : TaskPriority {
            return match($number) {
                0 => self::Lowest,
                1 => self::Low,
                2 => self::Medium,
                3 => self::High,
                4 => self::Highest,
                default => throw new \InvalidArgumentException("Invalid priority number '$number'.")
            };
        }
    }
?>