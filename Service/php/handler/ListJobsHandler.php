<?php
    class ListJobsHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("GetJobs", array("name" => $input["action"]));
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Jobs";
        }

        public function getPath() {
            return "/jobs";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("action", "string", "UpdateStats", TRUE));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of scheduled jobs with the specified action";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of scheduled jobs with the specified action. No locking is provided, which means that the job can be concurrently processed by multiple peers.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Scheduled jobs", 200, '[{"id":352299,"args":{"type":"YEAR","id":2024}},{"id":352300,"args":{"type":"CATEGORY","id":2}},{"id":352301,"args":{"type":"CATEGORY","id":3}},{"id":352302,"args":{"type":"ALL"}}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>