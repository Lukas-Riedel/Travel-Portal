<?php
    class GetCalendarIdentifierProcessor extends Processor {        
        public function process($input) {
            global $configuration;
    
            preg_match('/https:\/\/calendar\.google\.com\/calendar\/ical\/(.+@group\.calendar\.google\.com)\/.*/', rawurldecode($configuration["calendars"][$input["name"]]), $tokens);

            if (count($tokens) != 2) {
                throw new InvalidArgumentException("The calendar name " . $input["name"] . "is not valid.");
            }
            
            return $tokens[1];
        }

        public function getRequiredArguments() {
            return array("name");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>