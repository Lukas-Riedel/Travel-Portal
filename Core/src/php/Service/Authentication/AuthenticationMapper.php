<?php
    namespace Core\Service\Authentication;

    use Core\Client\Database\DatabaseClient;

    class AuthenticationMapper {

        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectUsersWithRole(string $role) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM user
                WHERE FIND_IN_SET(?, roles)
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($role)
                ->getMappedResultSet(function($userRow) {
                    return new User($userRow["id"], $userRow["username"], $userRow["password"], explode(",", $userRow["roles"]));
                });
        }

        public function selectUserById(string $id) : ?User {
            $sql = <<<'SQL'
                SELECT *
                FROM user
                WHERE id = ?
            SQL;
            
            $userRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($id)
                ->getSingleRow();

            if ($userRow === null) {
                return null;
            }

            return new User($userRow["id"], $userRow["username"], $userRow["password"], explode(",", $userRow["roles"]));
        }

        public function selectUserByUsername(string $username) : ?User {
            $sql = <<<'SQL'
                SELECT *
                FROM user
                WHERE username = ?
            SQL;
            
            $userRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($username)
                ->getSingleRow();

            if ($userRow === null) {
                return null;
            }

            return new User($userRow["id"], $userRow["username"], $userRow["password"], explode(",", $userRow["roles"]));
        }

        public function selectUserByApiKey(string $apiKey) : ?User {
            $sql = <<<'SQL'
                SELECT *
                FROM user
                WHERE api_key = ?
            SQL;
            
            $userRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($apiKey)
                ->getSingleRow();

            if ($userRow === null) {
                return null;
            }

            return new User($userRow["id"], $userRow["username"], $userRow["password"], explode(",", $userRow["roles"]));
        }

        public function updateUserPassword(string $username, string $password) : bool {
            $sql = <<<'SQL'
                UPDATE user
                SET password = ?
                WHERE username = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(password_hash($password, PASSWORD_DEFAULT), $username)
                ->execute() === 1;
        }
    }

?>