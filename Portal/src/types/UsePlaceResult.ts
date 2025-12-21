import type { Place } from "../classes/Place.ts";

export interface UsePlaceResult {
    place?: Place
    updatePlaceName: (name: string) => Promise<void>
    updatePlaceAddress: (address: string) => Promise<void>
    createPlaceHighlight: (photoId: string) => Promise<void>
    removePlaceHighlight: (highlightId: string) => Promise<void>
    updatePlaceMainHighlight: (highlightId: string) => Promise<void>
    updatePlaceHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) => Promise<void>
    createPlaceLabel: (name: string) => Promise<void>
    removePlaceLabel: (labelId: string) => Promise<void>
    updatePlaceExcerpt: (excerpt: string) => Promise<void>
    refreshPlaceExcerpt: () => Promise<void>
    updatePlaceLocation: (latitude: number, longitude: number) => Promise<void>
    updatePlaceAlbumReviewed: (albumId: string) => Promise<void>
    refreshPlaceAlbum: (albumId: string, mainPhotoPosition?: number) => Promise<void>
    createPlaceNote: (content: string) => Promise<void>
    updatePlaceNoteContent: (noteId: string, content: string) => Promise<void>
    removePlaceNote: (noteId: string) => Promise<void>
    updatePlaceCountry: (country: string) => Promise<void>
}