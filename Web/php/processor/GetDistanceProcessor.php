<?php
    class GetDistanceProcessor extends Processor {        
        public function process($input) {
            $deltaLatitude = $input["bLatitude"] - $input["aLatitude"];
            $deltaLongitude = $input["bLongitude"] - $input["aLongitude"];

            $alpha = $deltaLatitude / 2;
            $beta = $deltaLongitude / 2;

            $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($input["aLatitude"])) * cos(deg2rad($input["bLatitude"])) * sin(deg2rad($beta)) * sin(deg2rad($beta));
            $c = asin(min(1, sqrt($a)));

            return 2 * 6378 * $c;
        }

        public function getRequiredArguments() {
            return array("aLatitude", "aLongitude", "bLatitude", "bLongitude");
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>