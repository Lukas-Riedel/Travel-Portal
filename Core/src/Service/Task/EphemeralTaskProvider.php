<?php
    namespace Core\Service\Task;

    interface EphemeralTaskProvider {
        public function getSource() : string;
        public function getCandidates() : array;
    }
?>
