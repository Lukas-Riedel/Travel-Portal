<?php
    require_once(dirname(__FILE__) . "/GetTripHandler.php");
    
    class CreateTripExpenseHandler extends Handler {
        public function handle($input, $roles) {
            global $expenseService;

            $response = (new GetTripHandler())
                ->handle(array(
                    "tripId" => $input["tripId"]), $roles);
            if ($response["code"] != 200) {
                return $response;
            }

            $response = $expenseService->createExpense($input["tripId"], $input["value"], $input["currency"], $input["type"], $input["description"], isset($input["subscriptionId"]) ? $input["subscriptionId"] : NULL);
            return $this->createResponse(201, $response);
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
            return "/trips/{tripId}/expenses";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("tripId", "integer", 125));
        }

        public function getMethod() {
            return "POST";
        }
        
        public function getShortDescription() {
            return "Create an expense for the specified trip";
        }
        
        public function getLongDescription() {
            global $databaseProvider;
            $allowedTypes = explode(",", str_replace("'", "`", substr($databaseProvider
                ->statementBuilder("SELECT column_type FROM information_schema.COLUMNS WHERE TABLE_NAME = 'expense' AND COLUMN_NAME = 'type'")
                ->getSingleColumn("column_type"), 5, -1)));

            return "Creates an expense for the specified trip. The exchange rate to the configured main currency is fetched when creating the expense. The allowed expense types are: " . implode(", ", $allowedTypes);
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Create expense", '{"type":"ATTRACTION","description":"Archeologická naleziště v Římě","value":18,"currency":"EUR","subscriptionId":8}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Created expense", 201, '{"id":5215,"description":"Archeologická naleziště v Římě (WizzAir Club do 12.7.2025)","value":18,"currency":"EUR","mainCurrencyValue":577.9352776919483,"type":"ATTRACTION"}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>