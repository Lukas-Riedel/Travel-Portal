<?php
    class UpdateActualForecastProcessor extends Processor {        
        public function process($input) {
            global $placeService, $forecastService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($input["placeId"]);
            $forecastService->updateActualWeatherForecast($placeIdentifier->getId(), $input["start"], $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
        }

        public function getRequiredArguments() {
            return array("placeId", "start");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>