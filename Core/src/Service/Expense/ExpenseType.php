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
        case Hotel = "hotel";
        case Attraction = "attraction";
        case IntercityTransport = "intercityTransport";
        case PublicTransport = "publicTransport";
        case OrganizedTour = "organizedTour";
        case CarRental = "carRental";
        case Fuel = "fuel";
        case CityTax = "cityTax";
        case Parking = "parking";
        case AirportTransfer = "airportTransfer";
        case Visa = "visa";
        case Other = "other";

        public static function values(): array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>