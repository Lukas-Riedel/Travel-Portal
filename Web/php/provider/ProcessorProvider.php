<?php
    require_once(dirname(__FILE__) . "/../model/TargetError.php");
    require_once(dirname(__FILE__) . "/../exception/AuthorizationException.php");

    class ProcessorProvider {

        private $databaseProvider;
        private $schedulingProvider;
        private $loggingProvider;
        private $isExecutedFromApi;

        public function __construct($databaseProvider, $schedulingProvider, $loggingProvider, $isExecutedFromApi) {
            $this->databaseProvider = $databaseProvider;
            $this->schedulingProvider = $schedulingProvider;
            $this->loggingProvider = $loggingProvider;
            $this->isExecutedFromApi = $isExecutedFromApi;
        }

        public function run($processorName, $args) {  
            $onError = function($level, $message, $file, $line) {
                throw new RuntimeException($message);
            };

            if (!$this->isExecutedFromApi) {                
                $this->databaseProvider->beginTransaction();      
            }    
            try {
                set_error_handler($onError);
                $className = $processorName . "Processor";
                require_once(dirname(__FILE__) . "/../processor/" . $className . ".php");
                $processor = new $className;
                foreach ($processor->getRequiredArguments() as $requiredArgument) {
                    if ($args == NULL || !array_key_exists($requiredArgument, $args)) {
                        throw new InvalidArgumentException("The argument '" . $requiredArgument . "' is required.");
                    }
                }
                $result = $processor->process($args);
                if (!$this->isExecutedFromApi) {
                    $this->databaseProvider->commit();
                }
            }
            catch (Throwable $e) {
                if (!$this->isExecutedFromApi) {
                    $this->databaseProvider->rollback();
                }
                $error = new TargetError(400, $e, $args);
                $this->loggingProvider->logError(json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG));
                if ($this->isExecutedFromApi) {
                    throw e;
                }
                return $error;
            }
            finally {            
                restore_error_handler();
            }  
            return $result;
        }
    }
?>