<?php
    class SchedulingProvider {

        private $databaseProvider;
        private $configuration;

        public function __construct($databaseProvider, $configuration) {
            $this->databaseProvider = $databaseProvider;
            $this->configuration = $configuration;
        }
        
        public function scheduleJobExecution($processor, $args, $priority) {
            $argsJson = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG);

            $this->databaseProvider->beginTransaction();

            $this->databaseProvider
                ->statementBuilder("DELETE FROM queue_job WHERE processor = ? AND args = ? AND priority = ?")
                ->withParameters($processor, $argsJson, $priority == NULL ? $this->getDefaultProcessorPriority($processor) : $priority)
                ->execute();
                
            $this->databaseProvider
                ->statementBuilder("INSERT INTO queue_job (processor, args, priority) VALUES (?, ?, ?)")
                ->withParameters($processor, $argsJson, $priority == NULL ? $this->getDefaultProcessorPriority($processor) : $priority)
                ->execute();

            $this->databaseProvider->commit();
        }

        private function getDefaultProcessorPriority($processor) {
            return array_key_exists($processor, $this->configuration["processorsDefaultPriorities"]) ? $this->configuration["processorsDefaultPriorities"][$processor] : PHP_INT_MAX;
        }
    }
?>