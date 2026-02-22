<?php
    namespace Core\Client\Database;

    interface StatementBuilder {
        public function withParameters(mixed ...$params) : StatementBuilder;
        public function withDeferredParameters(mixed ...$params) : StatementBuilder;
        public function execute() : int;
        public function getResultSet() : array;
        public function getMappedResultSet(callable $fn) : array;
        public function getResultSetForColumn(string $column) : array;
        public function getMappedResultSetForColumn(string $column, callable $fn) : array;
        public function getSingleRow() : mixed;
        public function getFirstRow() : mixed;
        public function getSingleColumn(string $column) : mixed;
        public function getFirstColumn(string $column) : mixed;
    }
?>