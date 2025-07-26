import { fromUnixTime, isSameDay } from "date-fns";

export default class Place {
    constructor(place) {
        Object.assign(this, place)
    }

    isPermanent() {
        // TODO: This is not 100% true, the first part evaluates to true also for candidates
        return this.dates.length === 0 || this.dates.every(date => !date.trip)
    }

    getCategory(type) {
        if (type === "MOST_SPECIFIC_WITH_METADATA") {
            return this.categories?.findLast(category => category.metadata != null
                && category.metadata.color != null && category.metadata.unicode != null)
        }
        return this.categories?.findLast(category => category.category === type);
    }

    getEuclideanDistanceTo(place) {
        return Math.hypot(this.latitude - place.latitude, this.longitude - place.longitude)
    }

    getHaversineDistanceTo(place) {
        const toRad = x => x * Math.PI / 180;

        const x1 = place.latitude - this.latitude;
        const x2 = place.longitude - this.longitude;
        const a = Math.sin(toRad(x1) / 2) * Math.sin(toRad(x1) / 2) + Math.cos(toRad(this.latitude))
            * Math.cos(toRad(place.latitude)) * Math.sin(toRad(x2) / 2) * Math.sin(toRad(x2) / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return 6378 * c;
    }

    getPastTrips() {
        return [...new globalThis.Map(
            this.dates
                .filter(date => date.start < Date.now() / 1000)
                .map(date => date.trip)
                .filter(trip => trip !== null)
                .map(trip => [trip.id, trip]))
            .values()]
    }

    getAllTrips() {
        return [...new globalThis.Map(
            this.dates
                .map(date => date.trip)
                .filter(trip => trip !== null)
                .map(trip => [trip.id, trip]))
            .values()]
    }

    getAlbums() {
        return this.dates
            .map(date => date.album)
            .filter(album => album != null)
    }

    getAlbum(albumId) {
        return this.getAlbums().find(album => album.id === albumId)
    }

    getDateByAlbumId(albumId) {
        return this.dates.find(date => date.album?.id === albumId)
    }

    getDate(date) {
        return this.dates.find(d => isSameDay(date, fromUnixTime(d.start)))
    }
}