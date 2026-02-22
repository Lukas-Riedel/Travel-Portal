<?php
    namespace Core\Service\Configuration;

    use Core\Client\Database\DatabaseClient;

    class ConfigurationMapper {

        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectAllConfigurationEntries(bool $allowPrivate) : mixed {
            $sql = <<<'SQL'
                SELECT *
                FROM configuration
                WHERE private <= ?
                ORDER BY key ASC
            SQL;
            
            $configurationRows = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($allowPrivate ? 1 : 0)
                ->getResultSet();

            $configurationEntries = array();
            foreach ($configurationRows as &$configurationRow) {
                $configurationEntries[$configurationRow["key"]] = json_decode($configurationRow["value"], true);
            }
            return $configurationEntries;
        }
        
        public function selectConfigurationEntry(string $key) : mixed {
            $sql = <<<'SQL'
                SELECT *
                FROM configuration
                WHERE key = ?
            SQL;
            
            $configurationRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($key)
                ->getSingleRow();

            if ($configurationRow === null) {
                return null;
            }

            return json_decode($configurationRow["value"], true);;
        }

        public function updateConfigurationEntry(string $key, mixed $value) : bool {
            $sql = <<<'SQL'
                UPDATE configuration
                SET value = ?
                WHERE key = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(json_encode($value, JSON_UNESCAPED_UNICODE), $key)
                ->execute() === 1;
        }
    }
?>