<?php
    namespace Core\Service\Lifestyle;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Service\Configuration\ConfigurationService;

    class LifestyleService {

        private readonly LifestyleMapper $lifestyleMapper;
        private readonly GenerativeContentClient $generativeContentClient;
        private readonly ConfigurationService $configurationService;

        private readonly int $lifestyleRetentionDays;

        public function __construct(DatabaseClient $databaseClient, GenerativeContentClient $generativeContentClient, ConfigurationService $configurationService, int $lifestyleRetentionDays) {
            $this->lifestyleMapper = new LifestyleMapper($databaseClient);
            $this->generativeContentClient = $generativeContentClient;
            $this->configurationService = $configurationService;
            $this->lifestyleRetentionDays = $lifestyleRetentionDays;
        }

        public function createLifestyleEvent(LifestyleEventType $type, int $timestamp, ?string $content, ?float $calories) : LifestyleEvent {
            if ($type === LifestyleEventType::Intake && $calories === null) {
                $calories = floatval($this->generativeContentClient->getResponse($this->configurationService->getConfigurationEntry("generativeContentPrompt")["calories"], array("description" => $content)));
            }

            $event = new LifestyleEvent(null, $type, $timestamp, $content, $calories);
            $this->lifestyleMapper->insertLifestyleEvent($event);
            return $event;
        }

        public function getLifestyleEvents(?LifestyleEventType $type, ?string $from, ?string $to) : array {
            $this->lifestyleMapper->deleteStaleLifestyleEvents($this->lifestyleRetentionDays);
            return $this->lifestyleMapper->selectLifestyleEvents($type, $from, $to);
        }

        public function getLifestyleEvent(string $eventId) : ?LifestyleEvent {
            return $this->lifestyleMapper->selectLifestyleEvent($eventId);
        }

        public function removeLifestyleEvent(string $eventId) : bool {
            return $this->lifestyleMapper->deleteLifestyleEvent($eventId) > 0;
        }

        public function updateLifestyleEventContent(string $eventId, string $content) : bool {
            return $this->lifestyleMapper->updateLifestyleEventContent($eventId, $content);
        }
    }
?>
