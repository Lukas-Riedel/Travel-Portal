<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class CreatePlaceAlbumPhotoHandler extends Handler {
        public function handle($input) {
            global $photoService;            

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

            $response = $photoService->uploadPhoto($input["name"], $input["albumId"], isset($input["position"]) ? $input["position"] : NULL, 
                isset($input["replacePhotoId"]) ? $input["replacePhotoId"] : NULL, $input["data"]);
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
        
        public function getShortDescription() {
            return "Create a photo for an album with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Create a photo for an album with the specified identifier for the specified place. This operation uploads the photo to Google Photos but does not create an entry for it. It is eventually created and added to the album during the next album refresh.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create photo", '{"name":"77589a7e-a4d6-4931-9f42-ac809bdd27a7.jpg", "position": 1, "data":"base64"}'),
                $this->createRequestExample("Replace photo", '{"name":"77589a7e-a4d6-4931-9f42-ac809bdd27a7.jpg", "replacedPhotoId": 56743, "data":"base64"}'));
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