<?php
    require_once(dirname(__FILE__) . "/UpdateHighlightProcessor.php");

    class AddHighlightProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM " . $this->resolveHighlightTable($input["type"]) . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($input["id"], $input["highlightId"])
                ->execute();

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("id", "type", "highlightId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function resolveHighlightTable($type) {
            if ($type == "place") {
                return "highlight_place";
            }
            if ($type == "trip") {
                return "highlight_trip";
            }
            if ($type == "category") {
                return "highlight_category";
            }
            if ($type == "year") {
                return "highlight_year";
            }
            throw new InvalidArgumentException("Unknown highlight type " . $type . ". Permitted values: place, trip, category, year");
        }
    }
?>