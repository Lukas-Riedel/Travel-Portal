<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class RemoveTripHighlightHandler extends Handler {
        public function handle($input, $roles) {
            global $highlightService;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $highlightService->removeTripHighlight($input["tripId"], $input["highlightId"]);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Trip Highlights";
        }

        public function getPath() {
            return "/trips/{tripId}/highlights/{highlightId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 128),
                $this->createPathParameter("highlightId", "integer", 769));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a highlight with the specified identifier for the specified trip";
        }
        
        public function getLongDescription() {
            return "Removes a highlight with the specified identifier for the specified trip.";
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