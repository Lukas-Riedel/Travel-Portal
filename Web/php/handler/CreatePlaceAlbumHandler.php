<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class CreatePlaceAlbumHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $processorProvider->run("AddAlbum", $input);
            return $this->createResponse(201, $response);
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
            return "/places/{placeId}/albums";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create an album for the specified place";
        }
        
        public function getLongDescription() {
            return "Creates an album for the specified place with the specified timestamp. Timestamp is then used to do the matching between place events and albums. This effectively creates an album in the associated Google Photos account and fetches the empty album into the internal database. The album can also be created in UI of the associated Google Photos account, however, it can take some time until it is fetched into the database.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create album", '{"timestamp":1723903200}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created album", 201, '{"id":1120,"name":"Praha 17.8.2024","mainPhotoId":null,"mainImageUrl":"","permalink":"https://photos.google.com/lr/album/AGhjs2neOWtKlvmE7Reqmc6uHBU785HHFNOPGdKK2mMOtJ3mBYjr1TpHgdnpyviI8iAAzl_N0w1-","imagesCount":0,"indoorImagesCount":0}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>