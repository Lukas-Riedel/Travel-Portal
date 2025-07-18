<?php
    class ReplaceFitnessHandler extends Handler {
        public function handle($input) {
            global $fitnessService;
    
            $response = $fitnessService->updateFitnessRecord($input["timestamp"], $input["steps"],
                // TODO: Remove support for minutes one day.
                isset($input["minutes"]) ? (60 * $input["minutes"]) : $input["seconds"], $input["calories"], $input["distance"]);
            return $this->createResponse(204, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Fitness";
        }

        public function getPath() {
            return "/fitness/{timestamp}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("timestamp", "integer", 1734796800));
        }

        public function getMethod() {
            return "PUT";
        }
        
        public function getShortDescription() {
            return "Replace a fitness record with the specified timestamp with the specified one";
        }
        
        public function getLongDescription() {
            return "Replaces a fitness record with the specified timestamp with the specified one. If there is no record with this timestamp, a new one is created.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Replace fitness record", '{"steps":2209,"seconds":1384,"calories":149.2189295472,"distance":2037.8251443548}'));
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>