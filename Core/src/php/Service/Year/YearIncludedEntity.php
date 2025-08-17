<?php
    namespace Core\Service\Year;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "YearIncludedEntity",
        type: "string",
        description: "The entity of the year"
    )]
    enum YearIncludedEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>