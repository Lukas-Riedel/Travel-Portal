<?php
    class CreateTimeTrackingEventHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
    
            $response = $processorProvider->run("AddTimeTrackingEvent", $input);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Tracker";
        }

        public function getPath() {
            return "/tracker";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create a time tracking event";
        }
        
        public function getLongDescription() {
            global $databaseProvider;
            $allowedTypes = explode(",", str_replace("'", "`", substr($databaseProvider
                ->statementBuilder("SELECT column_type FROM information_schema.COLUMNS WHERE TABLE_NAME = 'tracking' AND COLUMN_NAME = 'type'")
                ->getSingleColumn("column_type"), 5, -1)));
            
            return "Creates a time tracking event. The allowed time tracking event types are: " . implode(", ", $allowedTypes);
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create time tracking event", '{"type":"OVERTIME","hours":"6.5","description":"Implementing DSD resolver for OpenLineage events","date":"7.9.2024"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created time tracking event", 201, '{"id":233,"description":"Implementing DSD resolver for OpenLineage events","hours":6.5,"timestamp":1725692400,"type":"OVERTIME","balance":17.3}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>