<?php
    namespace Core\Service\Authentication;

    class AuthenticationMapper {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectUsersWithRole(string $role) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM user
                WHERE FIND_IN_SET(?, roles)
            SQL;
            
            return $this->databaseProvider
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
            
            $userRow = $this->databaseProvider
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
            
            $userRow = $this->databaseProvider
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
            
            $userRow = $this->databaseProvider
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

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters(password_hash($password, PASSWORD_DEFAULT), $username)
                ->execute() === 1;
        }
    }

?>