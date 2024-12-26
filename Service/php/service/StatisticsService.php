<?php
    require_once(dirname(__FILE__) . "/../processor/GetStatsProcessor.php");

    class StatisticsService {
        public function getCategoryStatistics($categoryId) {            
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "category", 
                    "id" => $categoryId));
        }
        
        public function getPlaceStatistics($placeId) {            
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "place", 
                    "id" => $placeId));
        }
        
        public function getTripStatistics($tripId) {            
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "trip", 
                    "id" => $tripId));
        }

        public function getOverallStatistics() {        
            return (new GetStatsProcessor())
                ->process(array(
                    "type" => "all"));            
        }
    }
        
    enum StatisticsType {
        case Overall;
        case Trip;
        case Category;
        case Year;
    }
?>