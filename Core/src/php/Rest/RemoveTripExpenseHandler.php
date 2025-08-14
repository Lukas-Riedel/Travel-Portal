<?php
    require_once(__DIR__ . "/GetTripHandler.php");

    class RemoveTripExpenseHandler extends Handler {
        public function handle($input) {
            global $expenseService;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]));
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $expenseService->removeExpense($input["expenseId"], $input["tripId"]);
            if ($response === false) {                
                return $this->create404Response("trip_expenses", $input["expenseId"]);
            }

            return $this->createResponse(204, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return true;
        }

        public function getTag() {
            return "Trip Expenses";
        }

        public function getPath() {
            return "/trips/{tripId}/expenses/{expenseId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 125),
                $this->createPathParameter("expenseId", "integer", 5215));
        }

        public function getMethod() {
            return "DELETE";
        }
        
        public function getShortDescription() {
            return "Remove an expense with the specified identifier for the specified trip";
        }
        
        public function getLongDescription() {
            return "Removes an expense with the specified identifier for the specified trip.";
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->create204ResponseExample(),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>