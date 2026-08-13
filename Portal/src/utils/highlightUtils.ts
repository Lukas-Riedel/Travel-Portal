import { HighlightsTier } from "../types/HighlightsTier"
import type { Highlight } from "../types/CoreSwaggerTypes"

const LOW_IMPRESSION_THRESHOLD = 100
const LOW_COMPOSITION_THRESHOLD = 100
const LOW_SHADOWS_THRESHOLD = 60
const LOW_SKY_THRESHOLD = 50

// TODO: Move to PHP?
export function getHighlightsTier(highlights: Highlight[], mainHighlight?: Highlight): HighlightsTier {
    if (highlights.length === 0) {
        return HighlightsTier.F
    }

    const highlightsWithLowImpressionCount = highlights.filter(highlight => highlight.attributes.impression < LOW_IMPRESSION_THRESHOLD).length
    if (highlightsWithLowImpressionCount > highlights.length / 2) {
        return HighlightsTier.E
    }

    if (highlightsWithLowImpressionCount > 1) {
        return HighlightsTier.D
    }

    const highlightsWithLowCompositionCount = highlights.filter(highlight => highlight.attributes.composition < LOW_COMPOSITION_THRESHOLD).length
    const higlightsWithLowShadowsCount = highlights.filter(highlight => highlight.attributes.shadows < LOW_SHADOWS_THRESHOLD).length
    const highlightsWithLowSkyCount = highlights.filter(highlight => highlight.attributes.sky < LOW_SKY_THRESHOLD).length
    if (highlightsWithLowCompositionCount === highlights.length || higlightsWithLowShadowsCount === highlights.length || highlightsWithLowSkyCount === highlights.length
        || mainHighlight && (mainHighlight.attributes.composition < LOW_COMPOSITION_THRESHOLD || mainHighlight.attributes.shadows < LOW_SHADOWS_THRESHOLD || mainHighlight.attributes.sky < LOW_SKY_THRESHOLD)) {
        return HighlightsTier.C
    }

    if (highlightsWithLowCompositionCount > highlights.length / 2 || higlightsWithLowShadowsCount > highlights.length / 2 || highlightsWithLowSkyCount > highlights.length / 2) {
        return HighlightsTier.B
    }

    if (highlightsWithLowCompositionCount > 0 || higlightsWithLowShadowsCount > 0 || highlightsWithLowSkyCount > 0) {
        return HighlightsTier.A
    }

    return HighlightsTier.S
}