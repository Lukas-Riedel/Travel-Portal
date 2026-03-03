import type { Highlight, Year } from "./CoreSwaggerTypes.ts"

export interface UseYearResult {
    year?: Year
    createYearHighlight: (photoId: string) => Promise<Highlight>
    removeYearHighlight: (highlightId: string) => Promise<void>
    updateYearMainHighlight: (highlightId: string) => Promise<Year>
    updateYearHighlightQualityAttributes: (highlightId: string, composition: number, sky: number, shadows: number, circumstances: number, atmosphere: number) => Promise<Highlight>
    refreshYearHighlights: (count: number) => Promise<Highlight[]>
}