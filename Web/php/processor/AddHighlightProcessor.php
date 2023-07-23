<?php
    require_once(dirname(__FILE__) . "/GetHighlightIdentifierProcessor.php");

    class AddHighlightProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $highlightId = (new GetHighlightIdentifierProcessor())
                ->process(array(
                    "photoId" => $input["photoId"]));

            $table = $this->resolveTable($input["type"]);
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT * FROM " . $table . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($input["id"], $highlightId->getId())
                ->getSingleRow();

            if ($highlightRow == NULL) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO " . $table . " (id, highlight_id) VALUES (?, ?)")
                    ->withParameters($input["id"], $highlightId->getId())
                    ->execute();
            }
            
            return $highlightId;
        }

        public function getRequiredArguments() {
            return array("id", "type", "photoId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function resolveTable($type) {
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