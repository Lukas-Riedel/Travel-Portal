<?php
    class ResetTimeTrackingOpeningBalancesProcessor extends Processor {        
        public function process($input) {
            global $timeTrackingService;

            return $timeTrackingService->resetOpeningBalances();
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>