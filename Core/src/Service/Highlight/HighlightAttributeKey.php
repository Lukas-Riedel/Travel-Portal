<?php
    namespace Core\Service\Highlight;

    use Core\Service\Highlight\HighlightAttributes;

    enum HighlightAttributeKey : int {
        case Composition = 4;
        case Sky = 10;
        case Shadows = 10;
        case Circumstances = 4;
        case Atmosphere = 10;

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