<?php
    namespace Core\Service\Photo;

    class PhotoSimilarityCounts {

        private readonly int $total;
        private readonly int $matching;

        public function __construct(int $total, int $matching) {
            $this->total = $total;
            $this->matching = $matching;
        }

        public function getTotal() : int {
            return $this->total;
        }

        public function getMatching() : int {
            return $this->matching;
        }
    }
?>
