<?php
    abstract class Processor {
        abstract public function process($input);
        abstract public function getRequiredArguments();
        abstract public function requiresAdminRole();
    }
?>