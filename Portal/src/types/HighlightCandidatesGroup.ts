import type { Photo } from "./CoreSwaggerTypes"

export interface HighlightCandidatesGroup {
    title: string
    getPhotos: () => Promise<Photo[]>
}