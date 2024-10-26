<?php
    class AlbumService {
        public function getAlbumIdentifier($externalId) {
            global $databaseProvider;
            
            $albumIdentifier = $databaseProvider
                ->statementBuilder("SELECT id FROM album_identifier WHERE external_id = ?")
                ->withParameters($externalId)
                ->getFirstColumn("id");

            return $albumIdentifier;
        }
        
        public function getOrCreateAlbumIdentifier($externalId) {
            global $databaseProvider;

            $albumIdentifier = $this->getAlbumIdentifier($externalId);
            if ($albumIdentifier !== NULL) {
                return $albumIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO album_identifier (external_id) VALUES (?)")
                ->withParameters($externalId)
                ->execute();

            return $this->getAlbumIdentifier($externalId);
        }
    }
?>