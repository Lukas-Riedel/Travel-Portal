<?php
    class RemoveEventHandler extends Handler {
        public function handle($input) {
            global $eventManager;

            $response = $eventManager->removeEvent($input["eventId"]);
            return $this->createResponse(204, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Events";
        }

        public function getPath() {
            return "/events/{eventId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("eventId", "integer", 352299));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove a pending event with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Removes a pending event with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>