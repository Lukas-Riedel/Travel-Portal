import { endOfDay, format, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime, fromZonedTime } from "date-fns-tz"

export default class Trip {
    constructor(trip) {
        Object.assign(this, trip)
    }

    isDayTrips() {
        // TODO: Somehow use value from useConfiguration
        return this.name === "Výlety"
    }

    isPastDayTrips() {
        return this.isDayTrips() && this.year < new Date().getFullYear()
    }

    isCandidate() {
        return !this.year
    }

    isPast() {
        return this.end < Date.now() / 1000
    }

    isFuture() {
        return this.end > Date.now() / 1000
    }

    isCurrent() {
        const now = endOfDay(new Date()).getTime() / 1000
        return this.start < now && this.end > now
    }
    
    getFullName() {
        return this.year ? `${this.name} ${this.year}` : this.name
    }

    getEvents(day, places, timezone = undefined) {
        const dayString = day.toDateString()
        const flightEvents = (this.flights ?? [])
            .filter(f => startOfDay(toZonedTime(fromUnixTime(f.start), timezone || f.from.timezone)).toDateString() === dayString)
        const placeEvents = (places ?? []).flatMap(place => place.dates
            .filter(d => startOfDay(toZonedTime(fromUnixTime(d.start), timezone || place.timezone)).toDateString() === dayString)
            .map(date => ({ ...date, ...place }))
        )
        return [...flightEvents, ...placeEvents].sort((a, b) => a.start - b.start)
    }

    getStay(day) {
        // TODO: Somehow use value from useConfiguration
        const dayTimestamp = fromZonedTime(day, "Europe/Prague").getTime() / 1000
        // TODO: Use isWithinInterval.
        return [...(this.stays ?? [])].reverse().find(s => dayTimestamp >= s.start && (dayTimestamp + 86400) < s.end)
    }

    getPublicHoliday(day) {
        const key = format(day, "d.M.yyyy")
        return this.publicHolidays?.find(h => h.date === key)
    }
}
