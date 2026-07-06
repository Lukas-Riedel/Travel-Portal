import type { Highlight, Year } from "./CoreSwaggerTypes.ts"

export interface UseYearResult {
    year?: Year
    createYearHighlight: (photoId: string) => Promise<Highlight>
    removeYearHighlight: (highlightId: string) => Promise<void>
    updateYearMainHighlight: (highlightId: string) => Promise<Year>
    updateYearHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) => Promise<Highlight>
    refreshYearHighlights: (count: number) => Promise<Highlight[]>
}