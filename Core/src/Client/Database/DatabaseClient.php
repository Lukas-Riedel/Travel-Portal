<?php
    namespace Core\Client\Database;

    interface DatabaseClient extends TransactionManager {
        public function query(string $sql) : mixed;
        public function statementBuilder(string $sql, ?WhereClause $whereClause = null) : StatementBuilder;
        public function getIsNullOrEqualTo(?string $var) : string;
        public function getPlaceholdersSequence(int $count) : string;
    }
?>