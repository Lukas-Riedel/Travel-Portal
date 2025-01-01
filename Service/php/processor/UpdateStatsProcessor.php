<?php
    class UpdateStatsProcessor extends Processor {        
        public function process($input) {
            global $statisticsService, $tripService, $categoryService;

            if ($input["type"] === StatisticsType::Overall->value) {
                $statisticsService->updateOverallStatistics();
            }
            else if ($input["type"] === StatisticsType::Trip->value) {
                $trip = $tripService->getRegularTrip($input["id"]);
                if ($trip !== NULL) {
                    $statisticsService->updateTripStatistics($trip);                    
                }              
            }
            else if ($input["type"] === StatisticsType::Year->value) {
                $statisticsService->updateYearStatistics($input["id"]);
            }
            else if ($input["type"] === StatisticsType::Category->value) {
                $categoryIdentifier = $categoryService->getCategoryIdentifier($input["id"]);
                if ($categoryIdentifier !== NULL) {
                    $statisticsService->updateCategoryStatistics($categoryIdentifier);
                }
            }
        }

        public function getRequiredArguments() {
            return array("type");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>