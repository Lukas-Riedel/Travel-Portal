<?php
    class BackupDatabaseProcessor extends Processor {  
        public function process($input) {
            global $databaseProvider, $googleApiClient;

            $dump = array();

            foreach (explode(",", $input["tables"]) as &$table) {
                $dump[] = "-- " . $table;
        
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

                    $dump[] = "INSERT INTO " . $table . " (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ");";
                }

                $dump[] = "";
            }

            return $googleApiClient->createFile("Backup " . date("d.m.Y H:i:s") . ".sql", NULL, "application/sql", implode("\n", $dump));
        }

        public function getRequiredArguments() {
            return array("tables");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>