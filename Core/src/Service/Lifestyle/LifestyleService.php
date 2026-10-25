<?php
    namespace Core\Service\Lifestyle;

    use Core\Client\Database\DatabaseClient;

    class LifestyleService {

        private readonly LifestyleMapper $lifestyleMapper;

        private readonly int $lifestyleRetentionDays;

        public function __construct(DatabaseClient $databaseClient, int $lifestyleRetentionDays) {
            $this->lifestyleMapper = new LifestyleMapper($databaseClient);
            $this->lifestyleRetentionDays = $lifestyleRetentionDays;
        }

        public function createLifestyleEvent(LifestyleEventType $type, int $timestamp, string $content, ?float $calories) : LifestyleEvent {
            $event = new LifestyleEvent(null, $type, $timestamp, $content, $calories);
            $this->lifestyleMapper->insertLifestyleEvent($event);
            return $event;
        }

        public function getLifestyleEvents(?LifestyleEventType $type, ?string $from, ?string $to) : array {
            $this->lifestyleMapper->deleteStaleLifestyleEvents($this->lifestyleRetentionDays);
            return $this->lifestyleMapper->selectLifestyleEvents($type, $from, $to);
        }

        public function removeLifestyleEvent(string $eventId) : bool {
            return $this->lifestyleMapper->deleteLifestyleEvent($eventId) > 0;
        }
    }
?>
