<?php
    class CreatePlaceHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
    
            $response = $processorProvider->run("AddSpecialPlace", $input);
            return $this->createResponse(201, $response);
        }

        public function getTag() {
            return "Places";
        }

        public function getPath() {
            return "/places";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "POST";
        }

        public function getOperationId() {
            return "create_place";
        }
        
        public function getShortDescription() {
            return "Create a place";
        }
        
        public function getLongDescription() {
            return "Creates a place with the specified name and address. This can only create candidate and permanent places. To create a place event, it is necessary to do so in the associated Google Calendar account.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create candidate place", '{"type":"candidate","name":"Los Angeles","address":"Los Angeles, Spojené státy americké"}'),
                $this->createRequestExample("Create permanent place", '{"type":"permanent","name":"Dobrčice","address":"Dobrčice, Skršín, Česko"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created place", 201, '{"id":2572,"name":"Los Angeles","country":"Spojené státy americké","latitude":34.0549076,"longitude":-118.242643,"timezone":"America/Los_Angeles","categories":[],"dates":[],"imagesCount":0,"imagesScore":0}'),
                $this->create400ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>