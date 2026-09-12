import type { Label, LabelMetadata } from "./CoreSwaggerTypes.ts"

export interface UseLabelResult {
    label: Label | null,
    updateLabelName: (name: string) => Promise<Label>
    updateLabelMetadata: (metadata: LabelMetadata) => Promise<Label>
}