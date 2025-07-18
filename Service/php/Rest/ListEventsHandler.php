<?php
    class ListEventsHandler extends Handler {
        public function handle($input) {
            global $eventManager;

            $response = $eventManager->getEvents($input["name"]);
            return $this->createResponse(200, $response);
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
            return "/events";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("name", "string", "FitnessActivityDetected", TRUE));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of pending events with the specified name";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of pending events with the specified name. No locking is provided, which means that the event can be concurrently processed by multiple peers.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Pending events", 200, '[{"id":352299,"args":{"type":"YEAR","id":2024}},{"id":352300,"args":{"type":"CATEGORY","id":2}},{"id":352301,"args":{"type":"CATEGORY","id":3}},{"id":352302,"args":{"type":"ALL"}}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>