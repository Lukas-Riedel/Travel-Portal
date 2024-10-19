<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class ReplaceTripHandler extends Handler {
        public function handle($input) {
            global $processorProvider;
            
            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }
    
            $response = $processorProvider->run("LoadTrip", $input);
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Trips";
        }

        public function getPath() {
            return "/trips/{tripId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 128));
        }

        public function getMethod() {
            return "PUT";
        }

        public function getOperationId() {
            return "replace_trip";
        }
        
        public function getShortDescription() {
            return "Replace a trip with the specified identifier with a candidate trip";
        }
        
        public function getLongDescription() {
            return "Replaces a trip with the specified identifier with a candidate trip.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Replace trip", '{"candidateTripId":144}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Replaced trip", 200, '{"id":265,"name":"Balkán","year":2025,"mainHighlight":null,"start":1744322400,"end":1745100000,"countries":["Černá Hora","Kosovo","Severní Makedonie","Albánie"],"cost":1562.248514235976,"days":{"total":9,"working":5},"vacation":{"expected":3.75,"maximum":1.362776025236593},"expenses":[{"id":5206,"description":"Vídeň - Podgorica (WizzAir Club do 12.7.2025)","value":21.99,"currency":"EUR","mainCurrencyValue":723.3997769779372,"type":"FLIGHT"},{"id":5207,"description":"Tirana - Praha (WizzAir Club do 12.7.2025)","value":2660,"currency":"ALL","mainCurrencyValue":838.8487372580388,"type":"FLIGHT"}],"stays":[],"flights":[{"flight":"W42897","registration":null,"aircraft":null,"distance":null,"from":{"id":null,"name":"Vídeň","code":null,"country":null,"latitude":null,"longitude":null,"timezone":null},"to":{"id":null,"name":"Podgorica","code":null,"country":null,"latitude":null,"longitude":null,"timezone":null},"start":1744358400,"end":1744363500},{"flight":"W45137","registration":null,"aircraft":null,"distance":null,"from":{"id":null,"name":"Tirana","code":null,"country":null,"latitude":null,"longitude":null,"timezone":null},"to":{"id":null,"name":"Praha","code":null,"country":null,"latitude":null,"longitude":null,"timezone":null},"start":1745079600,"end":1745087100}],"watchedFlights":[],"layovers":[],"fitness":[],"notes":[{"id":26,"content":"<a href=\"https://travel.gjirafa.com/en\">Gjirafa Travel</a>"},{"id":27,"content":"<a href=\"https://www.balkanviator.com/en/\">Balkan Viator</a>"}],"highlights":[],"stats":[],"publicHolidays":[{"name":"Good Friday","country":"Albánie","date":"18.4.2025"},{"name":"Holy Saturday","country":"Albánie","date":"19.4.2025"}]}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>