<?php
    namespace Core\Client\Database;

    interface DatabaseClient extends TransactionManager {
        public function query(string $sql) : mixed;
        public function statementBuilder(string $sql, ?WhereClause $whereClause = null) : StatementBuilder;
        // TODO: Get rid of this method.
        public function whereClauseBuilder() : WhereClauseBuilder;
        public function getIsNullOrEqualTo(string $var) : string;
    }
?>