<?php
    namespace Core\Client\Database;

    class AtomicExecution {
        
        private array $afterCommitCallbacks = array();

        public function addAfterCommitCallback(callable $callback) : void {
            $this->afterCommitCallbacks[] = $callback;
        }

        public function commit() : void {
            foreach ($this->afterCommitCallbacks as &$callback) {
                $callback();
            }
        }
    }