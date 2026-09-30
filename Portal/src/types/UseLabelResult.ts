import type { Highlight, Label, LabelMetadata } from "./CoreSwaggerTypes.ts"

export interface UseLabelResult {
    label: Label | null
    removeLabel: () => Promise<void>
    updateLabelName: (name: string) => Promise<Label>
    updateLabelMetadata: (metadata: LabelMetadata) => Promise<Label>
    updateLabelMainHighlight: (highlightId: string) => Promise<Label>
    createLabelHighlight: (photoId: string) => Promise<Highlight>
    removeLabelHighlight: (highlightId: string) => Promise<void>
    updateLabelHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) => Promise<Highlight>
    refreshLabelHighlights: (count: number) => Promise<Highlight[]>
}