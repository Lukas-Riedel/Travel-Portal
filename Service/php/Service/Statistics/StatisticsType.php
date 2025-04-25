<?php
    namespace Service\Service\Statistics;

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