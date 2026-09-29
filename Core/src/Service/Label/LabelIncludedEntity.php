<?php
    namespace Core\Service\Label;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "LabelIncludedEntity",
        type: "string",
        description: "The entity of the label"
    )]
    enum LabelIncludedEntity : string {
        case Highlights = "highlights";
        
        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>
