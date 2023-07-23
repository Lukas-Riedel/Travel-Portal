<?php
    class RemoveTimeTrackingEventHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("RemoveTimeTrackingEvent", $input);
            if ($response === FALSE) {                
                return $this->create404Response("tracker", $input["eventId"]);
            }

            return $this->createResponse(204, $response);
        }

        public function getTag() {
            return "Tracker";
        }

        public function getPath() {
            return "/tracker/{eventId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("eventId", "integer", 233));
        }

        public function getMethod() {
            return "DELETE";
        }

        public function getOperationId() {
            return "remove_time_tracking_event";
        }
        
        public function getShortDescription() {
            return "Remove a time tracking event with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Removes a time tracking event with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>