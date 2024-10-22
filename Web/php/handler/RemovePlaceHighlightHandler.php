<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class RemovePlaceHighlightHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $processorProvider->run("RemoveHighlight", array(
                "id" => $input["placeId"],
                "type" => "place",
                "highlightId" => $input["highlightId"]));
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Place Highlights";
        }

        public function getPath() {
            return "/places/{placeId}/highlights/{highlightId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507),
                $this->createPathParameter("highlightId", "integer", 769));
        }

        public function getMethod() {
            return "DELETE";
        }

        public function getOperationId() {
            return "remove_place_highlight";
        }
        
        public function getShortDescription() {
            return "Remove a highlight with the specified identifier for the specified place";
        }
        
        public function getLongDescription() {
            return "Removes a highlight with the specified identifier for the specified place.";
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