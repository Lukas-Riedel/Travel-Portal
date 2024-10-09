<?php 
    require_once(dirname(__FILE__) . "/../model/HighlightIdentifier.php");

    class GetHighlightIdentifierProcessor extends Processor {   
        public function process($input) {
            global $databaseProvider, $configuration, $schedulingProvider;
            
            $highlightIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.photo_id = ?")
                ->withParameters($input["photoId"])
                ->getFirstRow();

            if ($highlightIdentifierRow != NULL) {
                return new HighlightIdentifier($highlightIdentifierRow["id"], $highlightIdentifierRow["thumbnail_url"], $highlightIdentifierRow["full_url"], $highlightIdentifierRow["focal_length"], 
                    $highlightIdentifierRow["aperture"], $highlightIdentifierRow["shutter_speed"], $highlightIdentifierRow["iso"], $highlightIdentifierRow["timestamp"]);
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO highlight_identifier (photo_id) VALUES (?)")
                ->withParameters($input["photoId"])
                ->execute();
                
            $highlightIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.photo_id = ?")
                ->withParameters($input["photoId"])
                ->getFirstRow();

            return new HighlightIdentifier($highlightIdentifierRow["id"], $highlightIdentifierRow["thumbnail_url"], $highlightIdentifierRow["full_url"], $highlightIdentifierRow["focal_length"], 
                $highlightIdentifierRow["aperture"], $highlightIdentifierRow["shutter_speed"], $highlightIdentifierRow["iso"], $highlightIdentifierRow["timestamp"]);
        }

        public function getRequiredArguments() {
            return array("photoId");
        }

        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>