<?php
    class GetMediaItemsProcessor extends Processor {        
        public function process($input) {
            global $photoService, $placeService, $albumService, $schedulingProvider;

            $photos = $photoService->getPhotos($input["albumId"]);
            $album = $albumService->getAlbum($input["albumId"]);

            // TODO: Should this code be here? Should ideally be in AlbumService but it cannot reference PlaceService.
            if (count($photos) !== $album->getImagesCount()) {
                $places = $placeService->getRegularPlaces(NULL, NULL, NULL, $input["albumId"], NULL, NULL, FALSE, FALSE, FALSE);

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        $trip = $date->getTrip();
                        if ($trip !== NULL) {
                            $schedulingProvider
                                ->scheduleJobExecution("UpdateStats", array(
                                    "type" => StatisticsType::Trip->value, 
                                    "id" => $trip->getId()), NULL);
                        }
                    }

                    foreach ($place->getCategories() as &$category) {
                        $schedulingProvider
                            ->scheduleJobExecution("UpdateStats", array(
                                "type" => StatisticsType::Category->value, 
                                "id" => $category->getId()), NULL);
                    }
                }
            }
        }

        public function getRequiredArguments() {
            return array("albumId");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>