<?php
    namespace Core\Client\GenerativeContent;

    class GenerativeContentResult implements \JsonSerializable {

        private readonly string $response;
        private readonly mixed $history;
        private readonly array $skills;
        private readonly array $topicIds;
        private readonly int $tokens;

        public function __construct(string $response, mixed $history, array $skills, array $topicIds, int $tokens) {
            $this->response = $response;
            $this->history = $history;
            $this->skills = $skills;
            $this->topicIds = $topicIds;
            $this->tokens = $tokens;
        }
        
        public function getResponse() : string {
            return $this->response;
        }

        public function getHistory() : mixed {
            return $this->history;
        }

        public function getSkills() : array {
            return $this->skills;
        }

        public function getTopicIds() : array {
            return $this->topicIds;
        }

        public function getTokens() : int {
            return $this->tokens;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
