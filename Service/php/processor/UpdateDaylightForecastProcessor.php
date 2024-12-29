<?php
    class UpdateDaylightForecastProcessor extends Processor {        
        public function process($input) {
            global $placeService, $forecastService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($input["placeId"]);
            $forecastService->updateDaylightForecast($placeIdentifier->getId(), $input["start"], $input["end"],
                $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
        }

        public function getRequiredArguments() {
            return array("placeId", "start", "end");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>