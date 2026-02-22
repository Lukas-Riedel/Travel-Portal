<?php
    namespace Core\Service\Expense;
        
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "ExpenseType",
        type: "string",
        description: "The type of the expense"
    )]
    enum ExpenseType : string {
        case Flight = "flight";
        case Transport = "transport";
        case Car = "car";
        case Hotel = "hotel";
        case Attraction = "attraction";
        case OrganizedTour = "organizedTour";
        case Visa = "visa";
        case Other = "other";

        public static function values(): array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>