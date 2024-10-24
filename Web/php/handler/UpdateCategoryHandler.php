<?php
    require_once(dirname(__FILE__) . "/GetCategoryHandler.php");

    class UpdateCategoryHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetCategoryHandler())
                ->handle(array(
                    "categoryId" => $input["categoryId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }     

            $response = $processorProvider->run("ChangeCategoryIdentifier", $input);
            if ($response instanceof TargetError) {
                return $this->createResponse(NULL, $response);
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
                $this->createPathParameter("categoryId", "integer", 1));
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
                $this->createRequestExample("Update category name", '{"name":"Evropa"}'),
                $this->createRequestExample("Update category main highlight", '{"mainHighlightId":1}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated category", 200, '{"id":281,"name":"Izrael","category":"COUNTRY","mainHighlight":{"id":965,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2kJjMLnQ1FwKB7lk1YdElxI6tobowUgm5T0kvzMva3n1bV1xl_n9uAsIOLg94BbAiautNYmrV3Og0hDQetuHp9L35XysQ.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.004999999,"iso":100,"timestamp":1681286621},"highlights":[{"id":548,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2n_EkL1IZQcL1zz0VnOm5os9nrmJLD_3zzkBAWRUq1NZk_-1z8ejWfp6kZonSEAOmSF3Z1eFgwPxTjLm7LfSviZ8t2hFg.jpg","full":null},"focalLength":16,"aperture":8,"shutterSpeed":0.008,"iso":100,"timestamp":1681397490},{"id":620,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2nA7ceNtwOmiM9JE9D8SLfh1verXp9omzleT23aYnd_mr3c6tS03UoDXls4FLGK37SRMhSJCnTrHQafXBcB0Z0-tw82dw.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1681389576},{"id":739,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2m_6DBFoO4-GI5cnwL6pRpu-rGWReQSY_GV27tt46L6_sGK6wcaoQtPm0Cw4lfV5TGdpYvR9fOVcZxsAEty_5JOCbJ5Jg.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1681381452},{"id":844,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2kAOmjKgderL30K_x6Fza_hP_q2C7TGGishwfDtC8ov-_w4hd7Vwxjf7Uz2E7RCunbb_07pphxH2MLYvKN072UTjqgEhw.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.003125,"iso":100,"timestamp":1681375686},{"id":965,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2kJjMLnQ1FwKB7lk1YdElxI6tobowUgm5T0kvzMva3n1bV1xl_n9uAsIOLg94BbAiautNYmrV3Og0hDQetuHp9L35XysQ.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.004999999,"iso":100,"timestamp":1681286621},{"id":1021,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2mXr2pGUGo0rEIXQg75ap-rcc0CoZaShs8Lz5l5F3irh77-6gjWxjO7cknS7MJjP9GpLJj1wgabmqtaaIj6k_Gf1e4TYA.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.004,"iso":100,"timestamp":1681216434},{"id":1077,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2ki3bL_F5u90VUdjqEaox0l8o7B9xQ-XS56nDVfLWVpKhU5GAs1eHkx8xX8NH2U7eqUiHBAJB1YPL7RUWbXAFOmeGgdTg.jpg","full":null},"focalLength":16,"aperture":11,"shutterSpeed":0.00625,"iso":100,"timestamp":1681203551}],"stats":[{"name":"TOTAL_PHOTOS_COUNT","value":436,"unit":"PHOTOS"},{"name":"AVERAGE_PHOTOS_PER_ALBUM","value":62,"unit":"PHOTOS"},{"name":"TOTAL_VISITED_PLACES_COUNT","value":7,"unit":"PLACES"},{"name":"TOTAL_TRAVEL_DAYS_COUNT","value":3,"unit":"DAYS"},{"name":"LAST_VISIT","value":1681399800,"unit":"BEFORE_DAYS_TIMESTAMP"},{"name":"MOST_PHOTOS_PER_PLACE","value":[{"key":"Jeruzalém","value":129},{"key":"Masada","value":111},{"key":"Caesarea","value":70},{"key":"Nazaret","value":47},{"key":"Akko","value":42}],"unit":"PHOTOS"},{"name":"LEAST_RECENTLY_VISITED_PLACES","value":[{"key":"Ejn Bokek","value":1681207200},{"key":"Masada","value":1681218000},{"key":"Jeruzalém","value":1681295400},{"key":"Caesarea","value":1681378200},{"key":"Haifa","value":1681383600}],"unit":"BEFORE_DAYS_TIMESTAMP"}]}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>