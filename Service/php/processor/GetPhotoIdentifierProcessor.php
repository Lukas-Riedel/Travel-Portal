<?php
    class GetPhotoIdentifierProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $photoIdentifier = $databaseProvider
                ->statementBuilder("SELECT id FROM photo_identifier WHERE external_id = ?")
                ->withParameters($input["externalId"])
                ->getFirstColumn("id");

            if ($photoIdentifier != NULL) {
                return $photoIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO photo_identifier (external_id) VALUES (?)")
                ->withParameters($input["externalId"])
                ->execute();

            return $databaseProvider
                ->statementBuilder("SELECT id FROM photo_identifier WHERE external_id = ?")
                ->withParameters($input["externalId"])
                ->getFirstColumn("id");
        }

        public function getRequiredArguments() {
            return array("externalId");
        }

        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>