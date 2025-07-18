<?php    
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");
    
    class RefreshPlaceAlbumHandler extends Handler {
        public function handle($input) {
            global $photoService;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }     

            $album = $response["body"]->findAlbum($input["albumId"]);
            if ($album == NULL) {
                return $this->create404Response("place_albums", $input["albumId"]);
            }

            $photoService->updateAlbum($input["albumId"], isset($input["mainPhotoPosition"]) ? $input["mainPhotoPosition"] : NULL);

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }     

            $album = $response["body"]->findAlbum($input["albumId"]);
            if ($album == NULL) {
                return $this->create404Response("place_albums", $input["albumId"]);
            }

            return $this->createResponse(200, $album);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Place Albums";
        }

        public function getPath() {
            return "/places/{placeId}/albums/{albumId}/refresh";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 5295),
                $this->createPathParameter("albumId", "integer", 227),
                $this->createQueryParameter("mainPhotoPosition", "integer", 1));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Refresh an album with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Refreshes an album with the specified identifier for the specified place. This effectively fetches content of the album as stored in the associated Google Photos synchronously, i.e., without a need of waiting for the next scheduled fetch.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Refreshed album", 200, '{"id":227,"name":"Serra de Santa Bárbara 21.2.2024","mainPhotoId":59603,"mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2mIL9vYy2Utq5LmdaMJjVZwscDHJsfXN9WWZjfGxsOwg7sWY576kcsRr5-hbhjS4osVWn64qHg88yOnmfoE9GjMAFko6Q.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2nXeoDvNyM1x-8Iy3YJD_mws3VXP9-h5a3CQH0_1jW_A5F_j8HYkNAqCUmfgLpPu802ob8U","imagesCount":8,"indoorImagesCount":0}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>