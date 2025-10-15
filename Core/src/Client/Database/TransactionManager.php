<?php
    namespace Core\Client\Database;

    interface TransactionManager {
        public function executeAtomically(callable $callable) : void;
        public function getCurentAtomicExecution() : ?AtomicExecution;
    }
?>