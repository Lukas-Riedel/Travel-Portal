<?php
    class RemoveJobHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = $processorProvider->run("RemoveJob", $input);
            return $this->createResponse(204, $response);
        }

        public function getTag() {
            return "Jobs";
        }

        public function getPath() {
            return "/jobs/{jobId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("jobId", "integer", 352299));
        }

        public function getMethod() {
            return "DELETE";
        }

        public function getOperationId() {
            return "remove_job";
        }
        
        public function getShortDescription() {
            return "Remove a job with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Removes a job with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample());
        }
    }
?>