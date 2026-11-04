<?php
    namespace Core\Service\Label;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;

    class LabelDataConsistencyMonitor implements DataConsistencyMonitor {

        private const LABEL_WITH_INCORRECT_HIGHLIGHTS_COUNT_ISSUE_NAME = "LABEL_WITH_INCORRECT_HIGHLIGHTS_COUNT";

        private readonly LabelService $labelService;
        private readonly ConfigurationService $configurationService;

        private readonly int $maxHighlightsPerLabelCount;

        public function __construct(LabelService $labelService, ConfigurationService $configurationService, int $maxHighlightsPerLabelCount) {
            $this->labelService = $labelService;
            $this->configurationService = $configurationService;
            $this->maxHighlightsPerLabelCount = $maxHighlightsPerLabelCount;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");
            $labelsWithIncorrectHighlightsCount = array_filter($this->labelService->getAllLabels(array(LabelIncludedEntity::Highlights->value)),
                fn($label) => !in_array($label->getName(), $dynamicLabelNames) && count($label->getHighlights()) !== $this->maxHighlightsPerLabelCount);
            foreach ($labelsWithIncorrectHighlightsCount as &$labelWithIncorrectHighlightsCount) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::LABEL_WITH_INCORRECT_HIGHLIGHTS_COUNT_ISSUE_NAME,
                    $labelWithIncorrectHighlightsCount->getId(), $labelWithIncorrectHighlightsCount, time());
            }

            return $dataConsistencyIssues;
        }
    }
?>