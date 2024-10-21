<?php
    require_once(dirname(__FILE__) . "/GetHighlightIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/UpdateHighlightProcessor.php");

    class AddHighlightProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $highlightIdentifier = (new GetHighlightIdentifierProcessor())
                ->process(array(
                    "photoId" => $input["photoId"]));

            $highlightTable = $this->resolveHighlightTable($input["type"]);
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT * FROM " . $highlightTable . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($input["id"], $highlightIdentifier->getId())
                ->getSingleRow();

            if ($highlightRow == NULL) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO " . $highlightTable . " (id, highlight_id) VALUES (?, ?)")
                    ->withParameters($input["id"], $highlightIdentifier->getId())
                    ->execute();

                $identifierTable = $this->resolveIdentifierTable($input["type"]);
                $databaseProvider
                    ->statementBuilder("UPDATE " . $identifierTable . " SET main_highlight_id = ? WHERE id = ? AND main_highlight_id IS NULL")
                    ->withParameters($highlightIdentifier->getId(), $input["id"])
                    ->execute();

                (new UpdateHighlightProcessor())
                    ->process(array(
                        "highlightId" => $highlightIdentifier->getId()));
            }

            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT * FROM " . $highlightTable . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($input["id"], $highlightIdentifier->getId())
                ->getSingleRow();
            
           return $highlightIdentifier;
        }

        public function getRequiredArguments() {
            return array("id", "type", "photoId");
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

        private function resolveIdentifierTable($type) {
            if ($type == "place") {
                return "place_identifier";
            }
            if ($type == "trip") {
                return "trip_identifier";
            }
            if ($type == "category") {
                return "category_identifier";
            }
            if ($type == "year") {
                return "year_identifier";
            }
            throw new InvalidArgumentException("Unknown highlight type " . $type . ". Permitted values: place, trip, category, year");
        }
    }
?>