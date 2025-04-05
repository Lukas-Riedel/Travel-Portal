<?php
    require_once(dirname(__FILE__) . "/../exception/EntityNotFoundException.php");

    class CreatePlaceLabelHandler extends Handler {
        public function handle($input) {
            global $labelService, $placeService;
            
            $placeIdentifier = $placeService->getPlaceIdentifierById($input["placeId"]);
            if ($placeIdentifier === NULL) {            
                throw new EntityNotFoundException("place", $input["placeId"]);
            }

            return $this->createResponse(201, $labelService->createLabel($placeIdentifier->getId(), $input["name"]));
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
            return "/places/{placeId}/labels";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a label for the specified place";
        }
        
        public function getLongDescription() {
            return "Creates a label for the specified place of the specified name.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create label", '{"name":"Hlavní město"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created label", 201, '{"id":1,"name":"Hlavní město"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>