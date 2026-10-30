<?php
    namespace Core\Service\GenerativeContent;

    use Common\Client\Cache\CacheClient;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryService;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Label\LabelService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Trip\TripService;
    use Core\Service\Year\YearService;
    use Ramsey\Uuid\Uuid;

    class GenerativeContentService {

        private const USER_ROLE = "user";

        private const MAX_COMPACTED_MESSAGES = 40;

        private const CONVERSATION_CACHE_KEY_FORMAT = "GenerativeContentService:Conversation:%s";
        private const CONVERSATION_CACHE_TTL = CommonConstants::ONE_DAY_SECONDS;

        private readonly TripService $tripService;
        private readonly PlaceService $placeService;
        private readonly CategoryService $categoryService;
        private readonly LabelService $labelService;
        private readonly YearService $yearService;
        private readonly ConfigurationService $configurationService;
        private readonly CacheClient $distributedCacheClient;
        private readonly GenerativeContentClient $generativeContentClient;
        
        private readonly int $tokenCompactificationThreshold;

        public function __construct(TripService $tripService, PlaceService $placeService, CategoryService $categoryService,
            LabelService $labelService, YearService $yearService, ConfigurationService $configurationService, CacheClient $distributedCacheClient,
            GenerativeContentClient $generativeContentClient, int $tokenCompactificationThreshold) {
            $this->tripService = $tripService;
            $this->placeService = $placeService;
            $this->categoryService = $categoryService;
            $this->labelService = $labelService;
            $this->yearService = $yearService;
            $this->configurationService = $configurationService;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->generativeContentClient = $generativeContentClient;
            $this->tokenCompactificationThreshold = $tokenCompactificationThreshold;
        }

        public function getChatResponse(string $prompt, string $accessToken, ?string $username, ?string $conversationId, mixed $environment, int $requestedHighlightsCount) : ?ChatResponse {
            $targetConversationId = $conversationId ?? Uuid::uuid4()->toString();
            $cacheKey = sprintf(self::CONVERSATION_CACHE_KEY_FORMAT, $targetConversationId);

            $lastResponse = $this->distributedCacheClient->get($cacheKey) ?? array();

            $messages = $lastResponse["history"] ?? array();
            if (($lastResponse["tokens"] ?? 0) > $this->tokenCompactificationThreshold) {
                $messages = $this->compactMessages($messages);
            }
            $messages[] = array("role" => self::USER_ROLE, "text" => $prompt);

            $skills = $lastResponse["skills"] ?? $this->configurationService->getConfigurationEntry("agenticAi")["defaultSkills"];

            $response = $this->generativeContentClient->getChatResponse($this->sanitizeChatHistory($messages), $accessToken, $username, $environment, $skills);
            if ($response === null) {
                return null;
            }

            $this->distributedCacheClient->set($cacheKey, $response, self::CONVERSATION_CACHE_TTL);

            return new ChatResponse($response->getResponse(), $targetConversationId, $this->getHighlights($response->getTopicIds(), $requestedHighlightsCount));
        }

        private function compactMessages(array $messages) : array {
            $newMessages = array();

            foreach ($messages as &$message) {
                $newParts = array();
                foreach ($message["parts"] as &$part) {
                    if (isset($part["text"])) {
                        $newParts[] = $part;
                    }
                }

                if (!empty($newParts)) {
                    $newMessages[] = array(
                        "parts" => $newParts,
                        "role" => $message["role"]
                    );
                }
            }

            return array_slice($newMessages, -self::MAX_COMPACTED_MESSAGES);
        }

        private function getHighlights(array $topicIds, int $requestedHighlightsCount) : array {
            if ($requestedHighlightsCount <= 0) {
                return array();
            }

            $highlightsLists = array();
            foreach ($topicIds as &$topicId) {
                $resolvedEntity = null;
                if (is_numeric($topicId)) {
                    $resolvedEntity = $this->yearService->getYear(intval($topicId));
                }
                else if (Uuid::isValid($topicId)) {
                    $resolvedEntity = $this->placeService->getRegularPlace($topicId)
                        ?? $this->tripService->getRegularTrip($topicId)
                        ?? $this->categoryService->getCategory($topicId)
                        ?? $this->labelService->getLabel($topicId);
                }

                if ($resolvedEntity === null) {
                    continue;
                }

                $highlights = $resolvedEntity->getHighlights();
                if (!empty($highlights)) {
                    shuffle($highlights);
                    // TODO: This doesn't respect whether the user has required roles to read the highlights or not.
                    $highlightsLists[] = $highlights;
                }
            }
            shuffle($highlightsLists);
            
            $highlights = array();
            for ($i = 0; $i < $requestedHighlightsCount; ++$i) {
                foreach ($highlightsLists as &$highlightsList) {
                    if (isset($highlightsList[$i])) {
                        $highlights[$highlightsList[$i]->getId()] = $highlightsList[$i];

                        if (count($highlights) >= $requestedHighlightsCount) {
                            break 2;
                        }
                    }
                }
            }
            return array_values($highlights);
        }

        // TODO: This exists only because empty objects are de/serialized as array, which leads to a type mismatch on the Python side. The correct solution is to ensure that empty objects are de/serialized correctly (e.g., stdClass).
        private function sanitizeChatHistory(array $messages): array {
            $messages = array_filter($messages, fn($message) => $message !== array());
            return array_values(array_map(fn($message) => $this->sanitizeChatMessage($message), $messages));
        }

        private function sanitizeChatMessage(mixed $data): mixed {
            if (is_array($data)) {
                if (isset($data["functionCall"]) && is_array($data["functionCall"])) {
                    if (array_key_exists("args", $data["functionCall"]) && empty($data["functionCall"]["args"])) {
                        $data["functionCall"]["args"] = new \stdClass();
                    }
                }
                return array_map(array($this, "sanitizeChatMessage"), $data);
            }
            return $data;
        }
    }
?>
