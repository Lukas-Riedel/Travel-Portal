import type { Label } from "./CoreSwaggerTypes.ts"

export interface UseLabelResult {
    label?: Label,
    updateLabelName: (name: string) => Promise<void>
}