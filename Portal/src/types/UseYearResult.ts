import type { Year } from "./CoreSwaggerTypes.ts";

export interface UseYearResult {
    year?: Year
    createYearHighlight: (photoId: string) => Promise<void>
    removeYearHighlight: (highlightId: string) => Promise<void>
    updateYearMainHighlight: (highlightId: string) => Promise<void>
    updateYearHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) => Promise<void>
}