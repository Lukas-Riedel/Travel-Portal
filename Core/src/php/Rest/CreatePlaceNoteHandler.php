<?php
    require_once(__DIR__ . "/GetPlaceHandler.php");
    
    class CreatePlaceNoteHandler extends Handler {
        public function handle($input) {
            global $noteService;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $noteService->createPlaceNote($input["placeId"], $input["content"]);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return true;
        }

        public function getTag() {
            return "Place Notes";
        }

        public function getPath() {
            return "/places/{placeId}/notes";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 125));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a note for the specified place";
        }
        
        public function getLongDescription() {
            return "Creates a note for the specified place.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create note", '{"content":"Obsah poznámky"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created note", 201, '{"id":83,"content":"Obsah poznámky"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>