<?php
    namespace Service\Service\Configuration;

    class ConfigurationMapper {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectAllConfigurationEntries(bool $allowPrivate) : mixed {
            $sql = <<<'SQL'
                SELECT *
                FROM configuration
                WHERE private <= ?
            SQL;
            
            $configurationRows = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($allowPrivate ? 1 : 0)
                ->getResultSet();

            $configurationEntries = array();
            foreach ($configurationRows as &$configurationRow) {
                $configurationEntries[$configurationRow["key"]] = json_decode($configurationRow["value"], TRUE);
            }
            return $configurationEntries;
        }
        
        public function selectConfigurationEntry(string $key) : mixed {
            $sql = <<<'SQL'
                SELECT *
                FROM configuration
                WHERE `key` = ?
            SQL;
            
            $configurationRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($key)
                ->getSingleRow();

            if ($configurationRow === NULL) {
                return NULL;
            }

            return json_decode($configurationRow["value"], TRUE);;
        }

        public function updateConfigurationEntry(string $key, mixed $value) : bool {
            $sql = <<<'SQL'
                UPDATE configuration
                SET value = ?
                WHERE `key` = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters(json_encode($value), $key)
                ->execute() === 1;
        }
    }
?>