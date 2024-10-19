<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class CreatePlaceAlbumPhotoHandler extends Handler {
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

            $response = $processorProvider->run("AddPhoto", $input);
            return $this->createResponse(204, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Place Album Photos";
        }

        public function getPath() {
            return "/places/{placeId}/albums/{albumId}/photos";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 5295),
                $this->createPathParameter("albumId", "integer", 227));
        }

        public function getMethod() {
            return "POST";
        }

        public function getOperationId() {
            return "create_place_album_photos";
        }
        
        public function getShortDescription() {
            return "Create a photo for an album with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Create a photo for an album with the specified identifier for the specified place. This operation uploads the photo to Google Photos but does not create an entry for it. It is eventually created and added to the album during the next album refresh.";
        }
        
        public function getRequestExamples() {
            return array($this->createRequestExample("Create photo", '{"name":"DSC01163.jpg", "position": 1, "data":"base64"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>