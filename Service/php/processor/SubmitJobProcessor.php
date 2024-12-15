<?php
    class SubmitJobProcessor extends Processor {  
        public function process($input) {
            global $schedulingProvider, $processorProvider;

            if ($input["type"] == "schedule") {
                $schedulingProvider
                    ->scheduleJobExecution($input["action"], $input["args"], NULL);
                return TRUE;
            }

            if ($input["type"] == "run") {
                return $processorProvider
                    ->run($input["action"], $input["args"]);
            }
            
            throw new InvalidArgumentException("Unknown job execution type " . $input["type"] . ". Permitted values: schedule, run");
        }

        public function getRequiredArguments() {
            return array("type", "action", "args");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>