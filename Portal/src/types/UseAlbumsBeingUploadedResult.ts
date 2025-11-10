import type { Date } from "./CoreSwaggerTypes.ts"

export interface UseAlbumsBeingUploadedResult {
    startedUploadingsCount: number
    isBeingUploaded: (date: Date) => boolean
}