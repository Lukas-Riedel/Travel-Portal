<?php    
    require_once(dirname(__FILE__) . "/../ical.php");
    require_once(dirname(__FILE__) . "/../model/PublicHoliday.php");

    class GetPublicHolidaysProcessor extends Processor {
        public function process($input) {
            global $configuration;

            if ($configuration["countries"][$input["country"]]["publicHolidaysCalendar"] == NULL) {
                return array();
            }
            
            $result = array();
            
            foreach ($this->downloadEvents($configuration["countries"][$input["country"]]["publicHolidaysCalendar"]) as &$holidayEvent) {
                $name = str_replace('\\', '', html_entity_decode($holidayEvent["SUMMARY"]));

                $timestamp = $this->getTimestamp($holidayEvent["DTSTART"]);                    
                if ($timestamp > time()) {
                    $date = getdate($timestamp);
                    $result[] = new PublicHoliday($name, $input["country"], $date["mday"] . "." . $date["mon"] . "." . $date["year"]);                    
                }
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array("country");
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function downloadEvents($url) {
            $data = file_get_contents($url);
            if ($data == FALSE) {
                throw new RuntimeException("Unable to download file from " . $url . ".");
            }
            return (new ICal(explode("\n", $data)))->cal["VEVENT"];
        }

        private function getTimestamp($date) {
            global $configuration;

            return (new DateTime($date, new DateTimeZone($configuration["homeLocation"]["timezone"])))->getTimestamp();
        }      
    }
?>