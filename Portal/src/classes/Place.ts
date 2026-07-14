import { fromUnixTime, isSameDay } from "date-fns"
import type { Place as IPlace, Category, Date, Highlight, Label, Note, TripIdentifier, Album, PlaceIdentifier } from "../types/CoreSwaggerTypes.ts"
import type { ExtendedCategoryCategory } from "../types/ExtendedCategoryCategory.ts"
import { getEuclideanDistance, getHaversineDistance } from "../utils/geocodingUtils.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import type { Coordinates } from "../types/Coordinates.ts"

export class Place implements IPlace {
    id: string
    name: string
    country: string
    latitude: number
    longitude: number
    elevation: number
    timezone: string
    mainHighlight?: Highlight
    score: number
    quality?: number
    excerpt?: string
    categories?: Category[]
    highlights?: Highlight[]
    labels?: Label[]
    notes?: Note[]
    nearbyPlaces?: PlaceIdentifier[]
    dates?: Date[]

    public constructor(data: IPlace) {
        Object.assign(this, data)
    }

    public withFilteredDates(dateFilter: (date: Date) => boolean): Place {
        return new Place({ ...this, dates: this.dates?.filter(dateFilter) })
    }

    public isPermanent(): boolean {
        // TODO: This is not 100% true, the first part evaluates to true also for candidates, the other part evaluates to true for day trip places
        return !this.dates || this.dates.every(date => !date.trip)
    }

    public getCategory(categoryCategory: ExtendedCategoryCategory): Category | undefined {
        if (categoryCategory === InternalCategoryCategory.MostSpecificWithMetadata) {
            return this.categories?.findLast(category => category.metadata != null
                && category.metadata.color != null && category.metadata.unicode != null)
        }
        return this.categories?.findLast(category => category.category === categoryCategory);
    }

    public getEuclideanDistanceTo(place: Coordinates): number {
        return getEuclideanDistance(place, this)
    }

    public getHaversineDistanceTo(place: Coordinates): number {
        return getHaversineDistance(place, this)
    }

    public getPastTrips(): TripIdentifier[] {
        return [...new Map(
            (this.dates ?? [])
                .filter(date => date.start < getCurrentOrMaximumAllowedTimestamp())
                .map(date => date.trip)
                .filter(Boolean)
                .map(trip => [trip.id, trip]))
            .values()]
    }

    public getAllTrips(): TripIdentifier[] {
        return [...new Map(
            (this.dates ?? [])
                .map(date => date.trip)
                .filter(Boolean)
                .map(trip => [trip.id, trip]))
            .values()]
    }

    public getAlbums(): Album[] {
        return (this.dates ?? [])
            .map(date => date.album)
            .filter(album => album != null)
    }

    public getAlbum(albumId: string): Album | undefined {
        return this.getAlbums().find(album => album.id === albumId)
    }

    public getDateByAlbumId(albumId: string): Date | undefined {
        return (this.dates ?? []).find(date => date.album?.id === albumId)
    }

    public getDate(date: string | number | globalThis.Date): Date | undefined {
        return (this.dates ?? []).find(d => isSameDay(date, fromUnixTime(d.start)))
    }
}