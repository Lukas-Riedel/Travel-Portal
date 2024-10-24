<?php
    class CreatePlaceHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
    
            $response = $processorProvider->run("AddSpecialPlace", $input);
            return $this->createResponse(201, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
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
                $this->createResponseExample("Created place", 201, '{"id":2572,"name":"Los Angeles","country":"Spojené státy americké","latitude":34.0549076,"longitude":-118.242643,"timezone":"America/Los_Angeles","mainHighlight":null,"excerpt":"Los Angeles, Město andělů, je dynamické a rozmanité město, které láká snem o slunci, pláži a hollywoodském glamour. Procházka po Sunset Boulevardu, kde se mísí historii s moderními architektonickými klenoty, vás zavede do světa filmů a hudebního průmyslu. Právě v Los Angeles se nachází slavný Hollywoodský chodník slávy a legendární Universal Studios, kde se můžete ponořit do světa filmu a zažít neuvěřitelné atrakce. Samozřejmě nesmíme zapomenout na pláže, jako je Venice Beach, s typickým surfováním a barevnými postranními uličkami plnými pouličních umělců a obchodů s suvenýry. Los Angeles je také domovem světoznámých muzeí, například Getty Center s úchvatnou sbírkou umění a krásných zahrad. Pokud hledáte adrenalinové zážitky, Disneyland Park a Six Flags Magic Mountain vám splní sny. Ať už se zajímáte o film, hudbu, historii, kulturu nebo jednoduše o slunce a pláž, Los Angeles vám zaručeně nabídne neuvěřitelné zážitky.","categories":[],"highlights":[],"dates":[],"imagesCount":0,"imagesScore":0}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>