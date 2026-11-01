import type { Label, LabelMetadata } from "./CoreSwaggerTypes.ts"

export interface UseLabelsResult {
    labels: Label[] | null
    createLabel: (name: string) => Promise<Label>
    updateLabelMetadata: (labelId: string, metadata: LabelMetadata) => Promise<Label>
    removeLabel: (labelId: string) => Promise<void>
}