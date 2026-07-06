import type { Place } from "../classes/Place.ts";
import type { Album, Highlight, Label, Note } from "./CoreSwaggerTypes.ts";

export interface UsePlaceResult {
    place?: Place
    updatePlaceName: (name: string) => Promise<Place>
    updatePlaceAddress: (address: string) => Promise<Place>
    createPlaceHighlight: (photoId: string) => Promise<Highlight>
    removePlaceHighlight: (highlightId: string) => Promise<void>
    updatePlaceMainHighlight: (highlightId: string) => Promise<Place>
    updatePlaceHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) => Promise<Highlight>
    createPlaceLabel: (name: string) => Promise<Label>
    removePlaceLabel: (labelId: string) => Promise<void>
    updatePlaceExcerpt: (excerpt: string) => Promise<Place>
    refreshPlaceExcerpt: () => Promise<Place>
    updatePlaceLocation: (latitude: number, longitude: number) => Promise<Place>
    updatePlaceAlbumsReviewed: () => Promise<Album[]>
    refreshPlaceAlbum: (albumId: string, mainPhotoPosition?: number, batchId?: string) => Promise<Album>
    createPlaceNote: (content: string) => Promise<Note>
    updatePlaceNoteContent: (noteId: string, content: string) => Promise<Note>
    removePlaceNote: (noteId: string) => Promise<void>
    updatePlaceCountry: (country: string) => Promise<Place>
    refreshPlaceHighlights: (count: number) => Promise<Highlight[]>
}