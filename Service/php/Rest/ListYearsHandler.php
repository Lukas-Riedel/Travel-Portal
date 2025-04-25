<?php
    class ListYearsHandler extends Handler {
        public function handle($input) {
            global $yearService;

            $response = $yearService->getYears(isset($input["include"]) ? explode(",", $input["include"]) : array());
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Years";
        }

        public function getPath() {
            return "/years";
        }

        public function getParameters() {
            return array(
                $this->createQueryParameter("include", "string", "HIGHLIGHTS,STATISTICS"));
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of years";
        }
        
        public function getLongDescription() {
            return "Retrieves a collection of years. Some fields in the result may be omitted due to performance reasons, these can be enabled by various include filters.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Years", 200, '[{"id":2025,"mainHighlight":null,"highlights":[],"statistics":[]},{"id":2024,"mainHighlight":{"id":190,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2kbKq_39nFNeBxaIGqbT3fgirov1aEfC4Kuh_EcPosFOoORGNJc3wqsmxookeuyCobaCHEZx3jpCJm1I01mk2q6fpExEg.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1718633818},"highlights":[],"statistics":[]},{"id":2022,"mainHighlight":{"id":281,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2kaedeQ3-uud-jph0uH3tBdEJC_MsoFUatHbGehfl2HWBibNll6uTruP9_2rXGvWCNxwgJ3gB0OG6NhsZ3CY80U-W5QZw.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.003125,"iso":100,"timestamp":1667991296},"highlights":[],"statistics":[]},{"id":2023,"mainHighlight":{"id":343,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2nXyAXl72H7WxdgVq0P0rwg9NalMZF32hsI2CISF-Nww8kCNely6Bdz5yjGx0gHk_ywq11nwC-qkw2IM7YlCTodc7otFw.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.008,"iso":100,"timestamp":1697703849},"highlights":[],"statistics":[]},{"id":2021,"mainHighlight":{"id":558,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2kEDnlraLsoyee7q-IIh6pL6jq6TLlNU3WqJ5E_nspxgEP6qRUMqA3CcWXL2feGmBuKbXA3Y7HXNJciIAqgerZfypHpUg.jpg","full":null},"focalLength":18,"aperture":11,"shutterSpeed":0.004,"iso":100,"timestamp":1637660673},"highlights":[],"statistics":[]},{"id":2020,"mainHighlight":{"id":810,"url":{"thumbnail":"https://lriedel.cz/cache/highlight/thumbnail/AGhjs2l-XDt_3jy_vO7184Lo1SRs_vXEYjQblwfmrcW10j7Je-0vPrb-L7czieCJ58Y8_5TDHmoKPWw9ccHlMZcRjptK93U6KQ.jpg","full":null},"focalLength":18,"aperture":9,"shutterSpeed":0.009999999,"iso":100,"timestamp":1599290351},"highlights":[],"statistics":[]}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>