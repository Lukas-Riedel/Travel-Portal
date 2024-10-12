<?php
    require_once(dirname(__FILE__) . "/../model/TargetError.php");

    class ProcessorProvider {

        private $databaseProvider;
        private $schedulingProvider;
        private $loggingProvider;
        private $isLoggedIn;
        private $shouldMaterializeViews;
        private $shouldIncludeTrace;

        public function __construct($databaseProvider, $schedulingProvider, $loggingProvider, $isLoggedIn, $shouldMaterializeViews, $shouldIncludeTrace) {
            $this->databaseProvider = $databaseProvider;
            $this->schedulingProvider = $schedulingProvider;
            $this->loggingProvider = $loggingProvider;
            $this->isLoggedIn = $isLoggedIn;
            $this->shouldMaterializeViews = $shouldMaterializeViews;
            $this->shouldIncludeTrace = $shouldIncludeTrace;
        }

        public function run($processorName, $args) {  
            $onError = function($level, $message, $file, $line) {
                throw new RuntimeException($message);
            };

            $this->databaseProvider->beginTransaction();          
            try {
                set_error_handler($onError);
                $className = $processorName . "Processor";
                require_once(dirname(__FILE__) . "/../processor/" . $className . ".php");
                $processor = new $className;
                if ($processor->requiresAdminRole() && !$this->isLoggedIn) {
                    throw new ErrorException("The action can only be executed by users with admin role.");
                }
                foreach ($processor->getRequiredArguments() as $requiredArgument) {
                    if ($args == NULL || !array_key_exists($requiredArgument, $args)) {
                        throw new InvalidArgumentException("The argument '" . $requiredArgument . "' is required.");
                    }
                }
                $result = $processor->process($args);
                $this->databaseProvider->commit();
            }
            catch (Throwable $e) {
                $this->databaseProvider->rollback();
                $error = new TargetError($e, $processorName, $args, $this->shouldIncludeTrace);
                $this->loggingProvider->logError(json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG));
                return $error;
            }
            finally {            
                restore_error_handler();
            }  
            if ($this->shouldMaterializeViews) {
                $this->databaseProvider->materializeViews();  
            }    
            return $result;
        }
    }
?>