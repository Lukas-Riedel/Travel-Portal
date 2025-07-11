<?php
    require_once(dirname(__FILE__) . "/GetHighlightHandler.php");

    class UpdateHighlightHandler extends Handler {
        public function handle($input, $roles) {
            global $highlightService, $databaseProvider;

            $response = (new GetHighlightHandler())
                ->handle(array(
                    "highlightId" => $input["highlightId"]), $roles);                    
            if ($response["code"] != 200) {
                return $response;
            }     

            if (isset($input["composition"])) {
                $highlightService->updateHighlightComposition($input["highlightId"], $input["composition"]);
            }

            if (isset($input["sky"])) {
                $highlightService->updateHighlightSky($input["highlightId"], $input["sky"]);
            }

            if (isset($input["shadows"])) {
                $highlightService->updateHighlightShadows($input["highlightId"], $input["shadows"]);
            }

            if (isset($input["circumstances"])) {
                $highlightService->updateHighlightCircumstances($input["highlightId"], $input["circumstances"]);
            }

            $databaseProvider->materializeViews();
            return (new GetHighlightHandler())
                ->handle(array(
                    "highlightId" => $input["highlightId"]), $roles);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Highlights";
        }

        public function getPath() {
            return "/highlights/{highlightId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("highlightId", "integer", 1));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update a highlight with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates a highlight with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update highlight composition quality", '{"composition":90}'),
                $this->createRequestExample("Update highlight sky quality", '{"sky":90}'),
                $this->createRequestExample("Update highlight shadows quality", '{"shadows":90}'),
                $this->createRequestExample("Update highlight circumstances quality", '{"circumstances":90}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated highlight", 200, '{}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>