<?php
    class ListCategoriesHandler extends Handler {
        public function handle($input) {
            global $categoryService;

            $response = $categoryService->getCategories(
                isset($input["country"]) ? $input["country"] : NULL,
                isset($input["categories"]) ? explode(",", $input["categories"]) : array(),
                isset($input["include"]) ? explode(",", $input["include"]) : array());
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Categories";
        }

        public function getPath() {
            return "/categories";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("country", "string", "Česko"),
                $this->createQueryParameter("categories", "string", "CONTINENT,COUNTRY,ADMINISTRATIVE,OCEAN,SEA,BAY,VARIABLE,ISLAND,REGION"),
                $this->createQueryParameter("include", "string", "HIGHLIGHTS,STATISTICS"));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of categories";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of categories matching the specified filters. Some fields in the result may be omitted due to performance reasons, these can be enabled by various include filters.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Categories", 200, '[{"id":1,"name":"Evropa","category":"CONTINENT","metadata":null,"mainHighlight":{"id":1386,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswmlzlEL79FQLhJFKH0vHMaFn5NeKOQGsIXNpgHjqHsgavOni2p54DrRCUJUUVmyXPdZiLwzsVIFbD7L4ClyoPzm5exvZA.jpg","full":null},"focalLength":105,"aperture":5,"shutterSpeed":0.002499999,"iso":100,"timestamp":1589629603},"highlights":[],"statistics":[]},{"id":5,"name":"Afrika","category":"CONTINENT","metadata":null,"mainHighlight":{"id":1892,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswkxmuD1Yp0OR7_gGcfsBAeYtDbUtY_3seEVwEaPaJOPCN_gcso3XCwkr_3U_bTSfyLsJNOWijX95XWIUplhKZ6hyfE9Qg.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1667629406},"highlights":[],"statistics":[]},{"id":19,"name":"Severní Amerika","category":"CONTINENT","metadata":null,"mainHighlight":{"id":2423,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswml5TE3FZDgTXiyINaXcVocBK0EUCNZshL9qFHfZ1pf6Da9VDE5NPqCYtoQTeygsXIA7zZS1a_fbzvTDiZ-cMinfM0JKQ.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1718473617},"highlights":[],"statistics":[]},{"id":21,"name":"Jižní Amerika","category":"CONTINENT","metadata":null,"mainHighlight":null,"highlights":[],"statistics":[]},{"id":38,"name":"Asie","category":"CONTINENT","metadata":null,"mainHighlight":{"id":1627,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswnYc1nd2QTPEB2Nh1JQ0fJIYKIv43SquQRO8MRFu5MFC46BcCagwGqkgBByFUBDDmZoL-rLJKv1hvrSHBSj3qNB_m7Tog.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1636969827},"highlights":[],"statistics":[]},{"id":650,"name":"Oceánie","category":"CONTINENT","metadata":null,"mainHighlight":null,"highlights":[],"statistics":[]}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>