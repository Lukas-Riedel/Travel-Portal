<?php
    namespace Core\Service\Place;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "PlaceIncludedEntity",
        type: "string",
        description: "The entity of the place"
    )]
    enum PlaceIncludedEntity : string {
        case Excerpt = "excerpt";
        case Categories = "categories";
        case Highlights = "highlights";
        case Labels = "labels";
        case Dates = "dates";
        case Notes = "notes";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>