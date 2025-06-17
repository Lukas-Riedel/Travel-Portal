export default class Trip {
    constructor(trip) {
        Object.assign(this, trip)
    }

    isDayTrips() {
        return this.name === "Výlety"
    }

    isCandidate() {
        return !this.year
    }

    isPast() {
        return this.end < Date.now() / 1000
    }

    isFuture() {
        return !this.isPast()
    }

    getFullName() {
        return this.year ? `${this.name} ${this.year}` : this.name
    }
}