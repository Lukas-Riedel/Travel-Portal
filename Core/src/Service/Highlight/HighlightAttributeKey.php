<?php
    namespace Core\Service\Highlight;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightAttributes;

    enum HighlightAttributeKey {
        case Composition;
        case Sky;
        case Shadows;
        case Circumstances;
        case Atmosphere;
        case Impression;

        public function getOptions(ConfigurationService $configurationService) : array {
            return $configurationService->getConfigurationEntry("highlight")["attribute"][strtolower($this->name)];
        }
        
        public function extractValue(HighlightAttributes $highlightAttributes) : ?float {
            return match($this) {
                self::Composition => $highlightAttributes->getComposition(),
                self::Sky => $highlightAttributes->getSky(),
                self::Shadows => $highlightAttributes->getShadows(),
                self::Circumstances => $highlightAttributes->getCircumstances(),
                self::Atmosphere => $highlightAttributes->getAtmosphere(),
                self::Impression => $highlightAttributes->getImpression()
            };
        }

        public static function createHighlightAttributes(array $rawAttributes) : HighlightAttributes {
            return new HighlightAttributes(
                $rawAttributes[self::Composition->name] ?? 100,
                $rawAttributes[self::Sky->name] ?? 100,
                $rawAttributes[self::Shadows->name] ?? 100,
                $rawAttributes[self::Circumstances->name] ?? 100,
                $rawAttributes[self::Atmosphere->name] ?? 100,
                $rawAttributes[self::Impression->name] ?? 100
            );
        }
    }
?>