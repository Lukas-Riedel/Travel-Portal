<?php
    namespace Service\Service\Authentication;

    class AuthenticationMapper {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectUserByUsername(string $username) : ?User {
            $sql = <<<'SQL'
                SELECT *
                FROM users
                WHERE username = ?
            SQL;
            
            $userRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($username)
                ->getSingleRow();

            if ($userRow === NULL) {
                return NULL;
            }

            return new User($userRow["username"], $userRow["password"], explode(",", $userRow["roles"]));
        }

        public function selectUserByApiKey(string $apiKey) : ?User {
            $sql = <<<'SQL'
                SELECT *
                FROM users
                WHERE api_key = ?
            SQL;
            
            $userRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($apiKey)
                ->getSingleRow();

            if ($userRow === NULL) {
                return NULL;
            }

            return new User($userRow["username"], $userRow["password"], explode(",", $userRow["roles"]));
        }

        public function updateUserPassword(string $username, string $password) : bool {
            $sql = <<<'SQL'
                UPDATE users
                SET password = ?
                WHERE username = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters(password_hash($password, PASSWORD_DEFAULT), $username)
                ->execute() === 1;
        }
    }

?>