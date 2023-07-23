<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");
    
    class UpdatePlaceAlbumHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }     

            $album = $response["body"]->findAlbum($input["albumId"]);
            if ($album == NULL) {
                return $this->create404Response("place_albums", $input["albumId"]);
            }
            
            // Set response for the case when the input is empty.
            $response = $this->createResponse(200, $album);

            $tags = array(
                "isMainForPlace" => "MAIN_FOR_PLACE",
                "isMainForCountry" => "MAIN_FOR_COUNTRY",
                "isMainForTrip" => "MAIN_FOR_TRIP",
                "isLowQuality" => "LOW_QUALITY",
                "isBadWeather" => "BAD_WEATHER");
    
            foreach ($input as $key => $value) {
                if (array_key_exists($key, $tags) && filter_var($value, FILTER_VALIDATE_BOOLEAN) != $album->{$key}()) {
                    $response = $processorProvider->run("ChangeAlbumStatus", array("placeId" => $input["placeId"], "albumId" => $input["albumId"], "type" => $tags[$key]));
                    if ($response instanceof TargetError) {
                        return $this->createResponse(NULL, $response);
                    }
                }
            }

            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Place Albums";
        }

        public function getPath() {
            return "/places/{placeId}/albums/{albumId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 5295),
                $this->createPathParameter("albumId", "integer", 227));
        }

        public function getMethod() {
            return "PATCH";
        }

        public function getOperationId() {
            return "update_place_album";
        }
        
        public function getShortDescription() {
            return "Update an album with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Updates an album with the specified identifier for the specified place.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Set low quality flag", '{"isLowQuality":true}'),
                $this->createRequestExample("Set bad weather flag", '{"isBadWeather":true}'),
                $this->createRequestExample("Mark as main for place", '{"isMainForPlace":true}'),
                $this->createRequestExample("Mark as main for country", '{"isMainForCountry":true}'),
                $this->createRequestExample("Mark as main for trip", '{"isMainForTrip":true}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated album", 200, '{"id":"654","name":"Praha 16.5.2020","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2kIrpOCCT54MdTt-oA1VE9oWRZp4V_uDZnaSZUvp8aM5tOtO_kWZDW9goBSC0nriO05LuKLRaPKcaAkQauVv7EsoxckQQ.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2lxV1nXHgWw_G6CAL9Sm-jnZX7wU5qM_ai2Ro6OzEv2olcSAwgaB_gmXUrep2CPl2ygyjxq","imagesCount":112,"indoorImagesCount":4,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>