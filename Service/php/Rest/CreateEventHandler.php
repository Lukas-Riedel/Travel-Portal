<?php
    class CreateEventHandler extends Handler {
        public function handle($input, $roles) {
            global $eventPublisher;

            $eventPublisher->publishEvent(Event::fromName($input["name"]), $input["args"]);
            return $this->createResponse(204, NULL);
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
            return array();
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create an event";
        }
        
        public function getLongDescription() {
            return "Creates an event. The event is added to the queue and eventually processed.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Event", '{"name":"CalendarInvalidated","args":{"calendar":"trips","watchId":"314f1767-a7e8-4e53-90a0-a392cc99eb5c"}}'));
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