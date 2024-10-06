<?php
    class ScheduleJobHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("SubmitJob", array("type" => "schedule") + $input);    
            return $this->createResponse(204, $response);
        }

        public function getTag() {
            return "Jobs";
        }

        public function getPath() {
            return "/jobs/schedule";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "POST";
        }

        public function getOperationId() {
            return "schedule_job";
        }
        
        public function getShortDescription() {
            return "Schedule a job";
        }
        
        public function getLongDescription() {
            return "Schedules a job. The job is added to a job queue and eventually processed.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Submitted job", '{"action":"UpdateCalendar","args":{"uuid":"314f1767-a7e8-4e53-90a0-a392cc99eb5c"}}'));
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample());
        }
    }
?>