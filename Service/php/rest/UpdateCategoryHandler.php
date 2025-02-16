<?php
    require_once(dirname(__FILE__) . "/GetCategoryHandler.php");

    class UpdateCategoryHandler extends Handler {
        public function handle($input) {
            global $categoryService;

            $response = (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }     

            if (isset($input["mainHighlightId"])) {
                $categoryService->updateCategoryMainHighlight($input["categoryId"], $input["mainHighlightId"]);
            }

            if (isset($input["name"])) {
                $categoryService->updateCategoryName($input["categoryId"], $input["name"]);
            }

            if (isset($input["metadata"]) && isset($input["color"])) {
                $categoryService->updateCategoryColor($input["categoryId"], $input["metadata"]["color"]);
            }

            if (isset($input["metadata"]) && isset($input["unicode"])) {
                $categoryService->updateCategoryUnicode($input["categoryId"], $input["metadata"]["unicode"]);
            }

            if (isset($input["metadata"]) && isset($input["publicHolidaysCalendar"])) {
                $categoryService->updateCategoryPublicHolidaysCalendar($input["categoryId"], $input["metadata"]["publicHolidaysCalendar"]);
            }

            return (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Categories";
        }

        public function getPath() {
            return "/categories/{categoryId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("categoryId", "integer", 281));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update a category with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates a category with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update category name", '{"name":"Izrael"}'),
                $this->createRequestExample("Update category main highlight", '{"mainHighlightId":145}'),
                $this->createRequestExample("Update category color", '{"metadata":{"color":"#012169"}}'),
                $this->createRequestExample("Update category unicode", '{"metadata":{"unicode":"1f1ec-1f1e7"}}'),
                $this->createRequestExample("Update category public holidays calendar", '{"metadata":{"publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.uk%23holiday%40group.v.calendar.google.com/public/basic.ics"}}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated category", 200, '{"id":281,"name":"Izrael","category":"COUNTRY","metadata":{"color":"#005EB8","unicode":"1f1ee-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.jewish%23holiday%40group.v.calendar.google.com/public/basic.ics"},"mainHighlight":{"id":1998,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswltDPHi3GOhU7n5Vm8P50xnqpAdm_hR_zwlkrymeEI8ZbQAh_9DXH0eGyn6rVd7UdGDL4lAbueyUH66dOgiqlWZzuXtKw.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.004999999,"iso":100,"timestamp":1681286621},"highlights":[{"id":1996,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswkKO8pWHw2wKCeeY-VoqqSI8kmulu0902VLH3dW9kZVXyYxqvPU3Mhu_9VLH-3Hec1m5vqMwqSnV5ut_GlJCaZExSE8Fw.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.00625,"iso":100,"timestamp":1681203551},{"id":1997,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswm_9H2uO4bjgy2q-x_YGO4Sm-ypZFUb8ta2ykDYyQnqBbrJuCcnYCsbkRuPcvMvY1OmdaU2ZJhFI8ubGgvK8dx_4WdBdQ.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.004,"iso":100,"timestamp":1681216434},{"id":1998,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswltDPHi3GOhU7n5Vm8P50xnqpAdm_hR_zwlkrymeEI8ZbQAh_9DXH0eGyn6rVd7UdGDL4lAbueyUH66dOgiqlWZzuXtKw.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.004999999,"iso":100,"timestamp":1681286621},{"id":2000,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswnCC2Xl8Y3GilonJ-gy-YZjbyT80ClbnkGbfdGtS7oDjQNcryy5vUhCsgLZ8mf3OHeUT4fBozrNDCtDL3go0a9Um6zHzQ.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.003125,"iso":100,"timestamp":1681375686},{"id":2001,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswlJksClrkFoqKM-CKl81R3KhcZgi0lck84YgO7NKUNnDUZMcONf4zP7ng0SZ79rFRChdu85D6tuPeh0dlzq4d_fyL0Bvw.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1681381452},{"id":2002,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswk2ggOGrrYqY5SZJswbMAdfnZ3UV-Ib-OZ9XWr_SIyye4akU00byhYClC3ROfGMqDJDAnT2dNV18VEz1s16kv58Wi7coQ.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1681389576},{"id":2003,"url":{"thumbnail":"https://lriedel.cz/api/cache/highlight/thumbnail/ADpjswnkHugCgQr4NKYT4kvLFxnzCGniJPma_GzI1Pbst-4kqqLuyRW9Gdb_AqHPBci-TVf2RDwh9RXXulhFR_CYjeCIARLv9Q.jpg","full":null},"focalLength":16,"aperture":8,"shutterSpeed":0.008,"iso":100,"timestamp":1681397490}],"statistics":[{"name":"TOTAL_PHOTOS_COUNT","value":436,"unit":"PHOTOS"},{"name":"AVERAGE_PHOTOS_PER_ALBUM","value":62,"unit":"PHOTOS"},{"name":"TOTAL_VISITED_PLACES_COUNT","value":7,"unit":"PLACES"},{"name":"TOTAL_TRAVEL_DAYS_COUNT","value":3,"unit":"DAYS"},{"name":"LAST_VISIT","value":1681399800,"unit":"BEFORE_DAYS_TIMESTAMP"},{"name":"MOST_PHOTOS_PER_PLACE","value":[{"key":"Jeruzalém","value":129},{"key":"Masada","value":111},{"key":"Caesarea","value":70},{"key":"Nazaret","value":47},{"key":"Akko","value":42}],"unit":"PHOTOS"},{"name":"LEAST_RECENTLY_VISITED_PLACES","value":[{"key":"Ejn Bokek","value":1681207200},{"key":"Masada","value":1681218000},{"key":"Jeruzalém","value":1681295400},{"key":"Caesarea","value":1681378200},{"key":"Haifa","value":1681383600}],"unit":"BEFORE_DAYS_TIMESTAMP"}]}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>