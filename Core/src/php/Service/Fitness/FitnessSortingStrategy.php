<?php
    namespace Core\Service\Fitness;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "FitnessSortingStrategy",
        type: "string",
        description: "The sorting strategy of the fitness records"
    )]
    enum FitnessSortingStrategy : string {
        case StepsAscending = "steps";
        case StepsDescending = "-steps";
        case TimeInMotionAscending = "seconds";
        case TimeInMotionDescending = "-seconds";        
        
        public function getOrderByClause() : string {
            return match ($this) {
                self::StepsAscending => "ORDER BY steps ASC",
                self::StepsDescending => "ORDER BY steps DESC",
                self::TimeInMotionAscending => "ORDER BY seconds DESC",
                self::TimeInMotionDescending => "ORDER BY seconds ASC"
            };
        }
    }
?>