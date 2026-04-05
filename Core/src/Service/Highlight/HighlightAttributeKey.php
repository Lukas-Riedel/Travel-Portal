<?php
    namespace Core\Service\Highlight;

    use Core\Service\Highlight\HighlightAttributes;

    enum HighlightAttributeKey {
        case Composition;
        case Sky;
        case Shadows;
        case Circumstances;
        case Atmosphere;

        public function getWeight(): int {
            return match($this) {
                self::Composition => 4,
                self::Sky => 10,
                self::Shadows => 10,
                self::Circumstances => 4,
                self::Atmosphere => 10
            };
        }
        
        public function extractValue(HighlightAttributes $highlightAttributes) : ?float {
            return match($this) {
                self::Composition => $highlightAttributes->getComposition(),
                self::Sky => $highlightAttributes->getSky(),
                self::Shadows => $highlightAttributes->getShadows(),
                self::Circumstances => $highlightAttributes->getCircumstances(),
                self::Atmosphere => $highlightAttributes->getAtmosphere()
            };
        }

        public static function createHighlightAttributes(array $rawAttributes) : HighlightAttributes {
            return new HighlightAttributes(
                $rawAttributes[self::Composition->name] ?? null,
                $rawAttributes[self::Sky->name] ?? null,
                $rawAttributes[self::Shadows->name] ?? null,
                $rawAttributes[self::Circumstances->name] ?? null,
                $rawAttributes[self::Atmosphere->name] ?? null
            );
        }
    }
?>