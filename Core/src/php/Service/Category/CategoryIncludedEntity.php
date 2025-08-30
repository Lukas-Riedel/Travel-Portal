<?php
    namespace Core\Service\Category;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "CategoryIncludedEntity",
        type: "string",
        description: "The entity of the category"
    )]
    enum CategoryIncludedEntity : string {
        case Statistics = "statistics";
        case Highlights = "highlights";
        
        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>