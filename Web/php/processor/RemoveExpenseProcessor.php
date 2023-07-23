<?php
    require_once(dirname(__FILE__) . "/UpdateCurrenciesProcessor.php");

    class RemoveExpenseProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM expense WHERE id = ?")
                ->withParameters($input["expenseId"])
                ->execute();
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $input["tripId"]), NULL);
                    
            (new UpdateCurrenciesProcessor())
                ->process(NULL);  

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("expenseId", "tripId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>