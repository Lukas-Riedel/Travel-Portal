<?php
    namespace Core\Client\Database;

    class WhereClause {
        
        private readonly string $clause;
        private readonly array $parameters;

        public function __construct(string $clause, array $parameters) {
            $this->clause = $clause;
            $this->parameters = $parameters;
        }

        public function getClause() : string {
            return $this->clause;
        }

        public function getParameters() : array {
            return $this->parameters;
        }
    }
?>