import { isSameDay } from "date-fns"

import type { Trip } from "../classes/Trip"
import type { Date, Flight, Place, TripIdentifier } from "../types/CoreSwaggerTypes"
import { getMaximumAllowedTimetamp, getTimezoneOrDefault, getZonedDate } from "./timeUtils"

export function isTripCandidate(trip: Trip | TripIdentifier): boolean {
    return !trip.year
}

export function getCalendarEvents(date: globalThis.Date, flights: Flight[] | undefined, watchedFlights: Flight[] | undefined, places: Place[], timezone?: string): (Flight | Omit<Flight, "flight"> | (Place & Date))[] {
    const flightEvents = (flights ?? [])
        .filter(f => f.start < getMaximumAllowedTimetamp() && isSameDay(date, getZonedDate(f.start, getTimezoneOrDefault(timezone || f.from.timezone))))
    const watchedFlightEvents = (watchedFlights ?? [])
        .filter(f => f.start < getMaximumAllowedTimetamp() && isSameDay(date, getZonedDate(f.start, getTimezoneOrDefault(timezone || f.from.timezone))))
        .map(({ flight: _flight, ...rest }): Omit<Flight, "flight"> & { flight?: undefined } => rest)
    const placeEvents = (places ?? []).flatMap(place => (place.dates ?? [])
        .filter(d => d.start < getMaximumAllowedTimetamp() && isSameDay(date, getZonedDate(d.start, getTimezoneOrDefault(timezone || place.timezone))))
        .map(date => ({ ...date, ...place })))

    return [...flightEvents, ...watchedFlightEvents, ...placeEvents].sort((a, b) => a.start - b.start)
}