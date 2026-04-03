<?php
    namespace Core\Client\Translation;

    interface TranslationClient {
        public function translate(string $text, string $sourceLanguage, string $targetLanguage) : string;
    }