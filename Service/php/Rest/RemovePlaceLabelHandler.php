<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class RemovePlaceLabelHandler extends Handler {
        public function handle($input) {
            global $labelService;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $wasRemoved = $labelService->removeLabelForPlace($input["placeId"], $input["labelId"]);
            if ($wasRemoved === FALSE) {                
                return $this->create404Response("place_labels", $input["labelId"]);
            }

            return $this->createResponse(204, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Place Labels";
        }

        public function getPath() {
            return "/places/{placeId}/labels/{labelId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507),
                $this->createPathParameter("labelId", "integer", 83));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a label with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Removes a label with the specified identifier for the specified place.";
        }
        
        public function getRequestExamples() {
            return array();
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