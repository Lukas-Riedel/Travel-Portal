<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "StatisticsType",
        type: "string",
        description: "The type of the statistics record"
    )]
    enum StatisticsType : string {
        case Overall = "ALL";
        case Trip = "TRIP";
        case Category = "CATEGORY";
        case Year = "YEAR";

        public function getTableName() : string {
            return match ($this) {
                self::Overall => "cache_statistics_all",
                self::Trip => "cache_statistics_trip",
                self::Category => "cache_statistics_category",
                self::Year => "cache_statistics_year"
            };
        }
    }
?>