<?php
    namespace Core\Client\Database;

    class WhereClauseBuilder {

        private array $clauses;
        private array $params;

        function __construct() {
            $this->clauses = array();
            $this->params = array();
        }

        public function withClause(string $clause, mixed ...$params) {
            $this->clauses[] = $clause;
            $this->params = array_merge($this->params, $params);
            return $this;
        }

        public function buildForAnd() {
            return $this->build("AND");
        }

        public function buildForOr() {
            return $this->build("OR");
        }

        private function build(string $glue) {
            return empty($this->clauses) 
                ? new WhereClause("", array())
                : new WhereClause("WHERE " . implode(" " . $glue . " ", $this->clauses), $this->params);
        }
    }
?>