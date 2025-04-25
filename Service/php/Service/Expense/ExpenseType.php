<?php
    namespace Service\Service\Expense;
    
    enum ExpenseType : string {
        case Flight = "FLIGHT";
        case Hotel = "HOTEL";
        case Attraction = "ATTRACTION";
        case IntercityTransport = "INTERCITY_TRANSPORT";
        case PublicTransport = "PUBLIC_TRANSPORT";
        case OrganizedTour = "ORGANIZED_TOUR";
        case CarRental = "CAR_RENTAL";
        case Fuel = "FUEL";
        case CityTax = "CITY_TAX";
        case Parking = "PARKING";
        case AirportTransfer = "AIRPORT_TRANSFER";
        case Visa = "VISA";
        case Other = "OTHER";

        public static function values(): array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>