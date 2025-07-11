<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");

    class UpdateTripExpenseHandler extends Handler {
        public function handle($input, $roles) {
            global $expenseService;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }

            if (isset($input["description"])) {
                $expenseService->updateExpenseDescription($input["expenseId"], $input["description"], $input["tripId"]);
            }

            if (isset($input["value"])) {
                $expenseService->updateExpenseValue($input["expenseId"], $input["value"], $input["tripId"]);
            }

            if (isset($input["currency"])) {
                $expenseService->updateExpenseCurrency($input["expenseId"], $input["currency"], $input["tripId"]);
            }

            $response = $expenseService->getExpense($input["expenseId"]);
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "ADMIN";
        }
        
        public function isProtected() {
            return TRUE;
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
                $this->createPathParameter("expenseId", "integer", 5202));
        }

        public function getMethod() {
            return "PATCH";
        }
        
        public function getShortDescription() {
            return "Update an expense with the specified identifier for the specified trip";
        }
        
        public function getLongDescription() {
            return "Updates an expense with the specified identifier for the specified trip. If the currency has changed, then the current exchange rate is fetched. Timestamp of the expense is not updated.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update expense description", '{"description":"Archeologická naleziště v Římě"}'),
                $this->createRequestExample("Update expense value", '{"value":18,"currency":"EUR"}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated expense", 200, '{"id":5215,"description":"Archeologická naleziště v Římě (WizzAir Club do 12.7.2025)","value":18,"currency":"EUR","mainCurrencyValue":577.9352776919483,"type":"ATTRACTION"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>