<?php
    namespace Core\Service\Index;

    use Common\Service\Authentication\UserRole;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "IndexableEntityType",
        type: "string",
        description: "The type of the indexable entity"
    )]
    enum IndexableEntityType : string {
        case Category = "category";
        case Place = "place";
        case Airport = "airport";
        case Airline = "airline";
        case Label = "label";
        case Trip = "trip";
        case Year = "year";
        
        public function getRequiredRole() : UserRole {
            return match($this) {
                self::Category => UserRole::CategoryRead,
                self::Place => UserRole::PlaceRead,
                self::Airport => UserRole::AirportRead,
                self::Airline => UserRole::AirlineRead,
                self::Label => UserRole::LabelRead,
                self::Trip => UserRole::TripRead,
                self::Year => UserRole::YearRead
            };
        }

        public function getPriority() : int {
            return match($this) {
                self::Place => 10,
                self::Trip => 8,
                self::Category => 5,
                self::Airport => 4,
                self::Airline => 2,
                self::Label => 2,
                self::Year => 1
            };
        }
    }
?>