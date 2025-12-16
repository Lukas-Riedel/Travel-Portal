<?php
    namespace Core\Client\Messaging;

    interface ProgressReporter {
        public function recordProgress() : void;
    }
?>