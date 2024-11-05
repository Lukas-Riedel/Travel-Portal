<?php
    require_once(dirname(__FILE__) . "/../model/Highlight.php");

    class HighlightService {
        public function getHighlight($highlightId) : ?Highlight {
            global $databaseProvider, $photoService;            
                
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT * FROM highlight_identifier WHERE id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();

            if ($highlightRow === NULL) {
                return NULL;
            }

            $photo = $photoService->getPhoto($highlightRow["photo_id"]);
            if ($photo === NULL) {
                return NULL;
            }
            
            return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(), $photo->getTimestamp());
        }

        public function getHighlightIdentifier($photoId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT id FROM highlight_identifier WHERE photo_id = ?")
                ->withParameters($photoId)
                ->getFirstColumn("id");
        }
        
        public function getOrCreateHighlightIdentifier($photoId) : string {
            global $databaseProvider;

            $highlightIdentifier = $this->getHighlightIdentifier($photoId);
            if ($highlightIdentifier !== NULL) {
                return $highlightIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO highlight_identifier (photo_id) VALUES (?)")
                ->withParameters($photoId)
                ->execute();

            return $this->getHighlightIdentifier($photoId);
        }
    }
?>