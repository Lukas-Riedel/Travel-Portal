<?php
    class ListTimeTrackingEventsHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetTimeTrackingEvents", $input);
            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Tracker";
        }

        public function getPath() {
            return "/tracker";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("type", "string", "VACATION"));
        }

        public function getMethod() {
            return "GET";
        }

        public function getOperationId() {
            return "list_time_tracking_events";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of time tracking events";
        }
        
        public function getLongDescription() {
            global $databaseProvider;
            $allowedTypes = explode(",", str_replace("'", "`", substr($databaseProvider
                ->statementBuilder("SELECT column_type FROM information_schema.COLUMNS WHERE TABLE_NAME = 'tracking' AND COLUMN_NAME = 'type'")
                ->getSingleColumn("column_type"), 5, -1)));

            return "Retrieves a collection of time tracking events based on the filter configuration. The empty filter returns all time tracking events. The allowed time tracking event types are: " . implode(", ", $allowedTypes);
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Time tracking events", 200, '[{"id":165,"description":"Balance usage","hours":-6.4,"timestamp":1719039600,"type":"VACATION","balance":134.2},{"id":164,"description":"Balance usage","hours":-6.4,"timestamp":1718953200,"type":"VACATION","balance":140.6},{"id":167,"description":"Workload adjustment","hours":4,"timestamp":1717225200,"type":"VACATION","balance":147},{"id":135,"description":"Balance usage","hours":-6.5,"timestamp":1716879600,"type":"VACATION","balance":143},{"id":134,"description":"Balance usage","hours":-6.5,"timestamp":1716793200,"type":"VACATION","balance":149.5},{"id":89,"description":"Balance usage","hours":-6.5,"timestamp":1713250800,"type":"VACATION","balance":156},{"id":75,"description":"Balance usage","hours":-6.5,"timestamp":1711612800,"type":"VACATION","balance":162.5},{"id":74,"description":"Balance usage","hours":-6.5,"timestamp":1711526400,"type":"VACATION","balance":169},{"id":73,"description":"Balance usage","hours":-6.5,"timestamp":1711440000,"type":"VACATION","balance":175.5},{"id":12,"description":"Balance usage","hours":-6.5,"timestamp":1710489600,"type":"VACATION","balance":182},{"id":11,"description":"Balance usage","hours":-6.5,"timestamp":1710403200,"type":"VACATION","balance":188.5},{"id":10,"description":"Balance usage","hours":-6.5,"timestamp":1710316800,"type":"VACATION","balance":195},{"id":7,"description":"Balance usage","hours":-6.5,"timestamp":1708675200,"type":"VACATION","balance":201.5},{"id":9,"description":"Balance usage","hours":-6.5,"timestamp":1706256000,"type":"VACATION","balance":208},{"id":8,"description":"Balance usage","hours":-6.5,"timestamp":1706169600,"type":"VACATION","balance":214.5},{"id":3,"description":"Opening balance","hours":221,"timestamp":1704096000,"type":"VACATION","balance":221}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample());
        }
    }
?>