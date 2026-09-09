import type { Label } from "./CoreSwaggerTypes.ts"

export interface UseLabelResult {
    label: Label | null,
    updateLabelName: (name: string) => Promise<Label>
}