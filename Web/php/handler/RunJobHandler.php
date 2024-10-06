<?php
    class RunJobHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("SubmitJob", array("type" => "run") + $input);    
            return $this->createResponse(200, $response);
        }

        public function getTag() {
            return "Jobs";
        }

        public function getPath() {
            return "/jobs/run";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "POST";
        }

        public function getOperationId() {
            return "run_job";
        }
        
        public function getShortDescription() {
            return "Run a job";
        }
        
        public function getLongDescription() {
            return "Runs a job. The job is executed synchronously and its results are returned.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Submitted job", '{"action":"UpdateCalendar","args":{"uuid":"314f1767-a7e8-4e53-90a0-a392cc99eb5c"}}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Job results", 200, 'true'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample());
        }
    }
?>