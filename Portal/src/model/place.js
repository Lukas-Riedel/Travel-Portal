import { fromUnixTime, isSameDay } from "date-fns";
import { getEuclideanDistance, getHaversineDistance } from "../utils/helpers";

export default class Place {
    constructor(place) {
        Object.assign(this, place)
    }

    isPermanent() {
        // TODO: This is not 100% true, the first part evaluates to true also for candidates
        return this.dates.length === 0 || this.dates.every(date => !date.trip)
    }

    getCategory(type) {
        if (type === "mostSpecificWithMetadata") {
            return this.categories?.findLast(category => category.metadata != null
                && category.metadata.color != null && category.metadata.unicode != null)
        }
        return this.categories?.findLast(category => category.category === type);
    }

    getEuclideanDistanceTo(place) {
        return getEuclideanDistance(place, this)
    }

    getHaversineDistanceTo(place) {
        return getHaversineDistance(place, this)
    }

    getPastTrips() {
        return [...new globalThis.Map(
            (this.dates ?? [])
                .filter(date => date.start < Date.now() / 1000)
                .map(date => date.trip)
                .filter(Boolean)
                .map(trip => [trip.id, trip]))
            .values()]
    }

    getAllTrips() {
        return [...new globalThis.Map(
            (this.dates ?? [])
                .map(date => date.trip)
                .filter(Boolean)
                .map(trip => [trip.id, trip]))
            .values()]
    }

    getAlbums() {
        return (this.dates ?? [])
            .map(date => date.album)
            .filter(album => album != null)
    }

    getAlbum(albumId) {
        return this.getAlbums().find(album => album.id === albumId)
    }

    getDateByAlbumId(albumId) {
        return (this.dates ?? []).find(date => date.album?.id === albumId)
    }

    getDate(date) {
        return (this.dates ?? []).find(d => isSameDay(date, fromUnixTime(d.start)))
    }
}