<?php
    class GetConfigurationProcessor extends Processor {
        public function process($input) {
            global $configurationProvider;

            $levels = explode(",", $input["levels"]);
            if (in_array(PRIVATE_CONFIGURATION, $levels)) {
                throw new ErrorException("The user is not authorized to view private configuration.");
            }
            
            return $configurationProvider
                ->get(...$levels);
        }

        public function getRequiredArguments() {
            return array("levels");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>