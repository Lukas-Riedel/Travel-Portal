<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");
    
    class CreateTripNoteHandler extends Handler {
        public function handle($input, $roles) {
            global $noteService;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $noteService->createNote($input["tripId"], $input["content"]);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Trip Notes";
        }

        public function getPath() {
            return "/trips/{tripId}/notes";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 125));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a note for the specified trip";
        }
        
        public function getLongDescription() {
            return "Creates a note for the specified trip.";
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