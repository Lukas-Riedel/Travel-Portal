<?php
    class GetAlbumIdentifierProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $albumIdentifier = $databaseProvider
                ->statementBuilder("SELECT id FROM album_identifier WHERE external_id = ?")
                ->withParameters($input["externalId"])
                ->getFirstColumn("id");

            if ($albumIdentifier != NULL) {
                return $albumIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO album_identifier (external_id) VALUES (?)")
                ->withParameters($input["externalId"])
                ->execute();

            return $databaseProvider
                ->statementBuilder("SELECT id FROM album_identifier WHERE external_id = ?")
                ->withParameters($input["externalId"])
                ->getFirstColumn("id");
        }

        public function getRequiredArguments() {
            return array("externalId");
        }

        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>