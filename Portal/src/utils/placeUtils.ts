import { fromUnixTime, isSameDay } from "date-fns"

import type { Album, Category, Date, Highlight, Place, TripIdentifier } from "../types/CoreSwaggerTypes.ts"
import type { ExtendedCategoryCategory } from "../types/ExtendedCategoryCategory.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { getEuclideanDistance, getHaversineDistance } from "./geocodingUtils.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "./timeUtils.ts"
import type { Coordinates } from "../types/Coordinates.ts"

export function filterPlaceDates(place: Place, dateFilter: (date: Date) => boolean): Place {
    return { ...place, dates: place.dates?.filter(dateFilter) }
}

export function isPlacePermanent(place: Place): boolean {
    // TODO: This is not 100% true, the first part evaluates to true also for candidates, the other part evaluates to true for day trip places
    return !place.dates || place.dates.every(date => !date.trip)
}

export function getPlaceCategory(place: Place, categoryCategory: ExtendedCategoryCategory): Category | null {
    if (categoryCategory === InternalCategoryCategory.MostSpecificWithMetadata) {
        return place.categories?.findLast(category => category.metadata != null
            && category.metadata.color != null && category.metadata.unicode != null) ?? null
    }
    return place.categories?.findLast(category => category.category === categoryCategory) ?? null
}

export function getEuclideanDistanceTo(place: Place, coords: Coordinates): number {
    return getEuclideanDistance(coords, place)
}

export function getHaversineDistanceTo(place: Place, coords: Coordinates): number {
    return getHaversineDistance(coords, place)
}

export function getPastPlaceTrips(place: Place): TripIdentifier[] {
    return [...new Map(
        (place.dates ?? [])
            .filter(date => date.start < getCurrentOrMaximumAllowedTimestamp())
            .map(date => date.trip)
            .filter((trip): trip is TripIdentifier => trip != null)
            .map(trip => [trip.id, trip]))
        .values()]
}

export function getAllPlaceTrips(place: Place): TripIdentifier[] {
    return [...new Map(
        (place.dates ?? [])
            .map(date => date.trip)
            .filter((trip): trip is TripIdentifier => trip != null)
            .map(trip => [trip.id, trip]))
        .values()]
}

export function getPlaceAlbums(place: Place): Album[] {
    return (place.dates ?? [])
        .map(date => date.album)
        .filter((album): album is Album => album != null)
}

export function getPlaceAlbum(place: Place, albumId: string): Album | undefined {
    return getPlaceAlbums(place).find(album => album.id === albumId)
}

export function getPlaceDateByAlbumId(place: Place, albumId: string): Date | undefined {
    return (place.dates ?? []).find(date => date.album?.id === albumId)
}

export function getPlaceDate(place: Place, date: string | number | globalThis.Date): Date | undefined {
    return (place.dates ?? []).find(d => isSameDay(date, fromUnixTime(d.start)))
}

export function getPlaceMainHighlight(place: Place): Highlight | undefined {
    return place.mainHighlight
}
