<?php
    require_once(dirname(__FILE__) . "/CreateFileProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class BackupDatabaseProcessor extends Processor {  
        public function process($input) {
            global $databaseProvider;

            $backupFolder = $this->createBackupFolder();

            $ddls = array();
            $dmls = array();

            // Tables.
            foreach (explode(",", $input["tables"]) as &$table) {
                $dmls[] = "-- " . $table;
                $ddls[] = "-- " . $table;
        
                $rows = $databaseProvider
                    ->statementBuilder("SELECT * FROM " . $table)
                    ->getResultSet();

                foreach ($rows as &$row) {
                    $keys = array();
                    $values = array();

                    foreach ($row as $key => $value) {
                        $keys[] = "`" . $key . "`";
                        $values[] = $value === NULL ? "NULL" : ("'" . str_replace("'", "''", $value) . "'");
                    }

                    $dmls[] = "INSERT INTO " . $table . " (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ");";
                }

                $definitionRow = $databaseProvider
                    ->statementBuilder("SHOW CREATE TABLE " . $table)
                    ->getSingleRow();
                $ddls[] = $definitionRow["Create Table"] . ";";

                $dmls[] = "";
                $ddls[] = "";
            }
            
            // Views.
            foreach (explode(",", $input["views"]) as &$view) {
                $ddls[] = "-- " . $view;
                
                $definitionRow = $databaseProvider
                    ->statementBuilder("SHOW CREATE VIEW " . $view)
                    ->getSingleRow();
                $ddls[] = $definitionRow["Create View"] . ";";

                $ddls[] = "";
            }
            
            // Functions.
            foreach (explode(",", $input["functions"]) as &$function) {
                $ddls[] = "-- " . $function;
                
                $definitionRow = $databaseProvider
                    ->statementBuilder("SHOW CREATE FUNCTION " . $function)
                    ->getSingleRow();
                $ddls[] = $definitionRow["Create Function"] . ";";

                $ddls[] = "";
            }

            $createFileProcessor = new CreateFileProcessor();
            
            $createFileProcessor
                ->process(array(
                    "folderId" => $backupFolder, 
                    "name" => "Database Dump.sql",
                    "content" => implode("\n", $dmls), 
                    "contentType" => "application/sql"));

            $createFileProcessor
                ->process(array(
                    "folderId" => $backupFolder, 
                    "name" => "Database Objects.sql", 
                    "content" => implode("\n", $ddls), 
                    "contentType" => "application/sql"));

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("tables", "views", "functions");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function createBackupFolder() {
            $payload = array(
                "name" => "Backup " . date("d.m.Y H:i:s"), 
                "mimeType" => "application/vnd.google-apps.folder");
            
            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://www.googleapis.com/drive/v3/files", 
                    "payload" => json_encode($payload)))["id"];
        }
    }
?>