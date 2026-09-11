import { differenceInCalendarDays, endOfDay, format, fromUnixTime, isSameDay, startOfDay } from "date-fns"
import { fromZonedTime } from "date-fns-tz"

import type { Date, Flight, Place, PublicHoliday, Stay, Trip, TripIdentifier } from "../types/CoreSwaggerTypes"
import { getCurrentOrMaximumAllowedTimestamp, getEndOfTodayOrMaximumAllowedTimestamp, getMaximumAllowedTimetamp, getStartOfTodayOrMaximumAllowedTimestamp, getTimezoneOrDefault, getZonedDate, ONE_DAY_SECONDS } from "./timeUtils"

const PUBLIC_HOLIDAY_DATE_FORMAT = "d.M.yyyy"

export function isTripCandidate(trip: Trip | TripIdentifier): boolean {
    return !trip.year
}

export function getTripFullName(trip: Trip | TripIdentifier): string {
    return isTripCandidate(trip) ? trip.name : `${trip.name} ${trip.year}`
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

export function isPastTrip(trip: Trip): boolean {
    return trip.end! < getCurrentOrMaximumAllowedTimestamp()
}

export function isFutureTrip(trip: Trip): boolean {
    return trip.start! > getCurrentOrMaximumAllowedTimestamp()
}

export function isCurrentTrip(trip: Trip): boolean {
    return trip.start! <= getEndOfTodayOrMaximumAllowedTimestamp() && getStartOfTodayOrMaximumAllowedTimestamp() < trip.end!
}

export function isDayInTrip(trip: Trip, date: globalThis.Date): boolean {
    return trip.start! * 1000 <= endOfDay(date).getTime() && startOfDay(date).getTime() < trip.end! * 1000
}

export function isTripBetweenDates(trip: Trip, start: globalThis.Date, end: globalThis.Date, timezone?: string): boolean {
    return start < getZonedDate(trip.end!, getTimezoneOrDefault(timezone)) && getZonedDate(trip.start!, getTimezoneOrDefault(timezone)) < end
}

export function isStartDayOfTrip(trip: Trip, date: globalThis.Date): boolean {
    return isSameDay(fromUnixTime(trip.start!), date)
}

export function isEndDayOfTrip(trip: Trip, date: globalThis.Date): boolean {
    return isSameDay(fromUnixTime(trip.end!), date)
}

export function getTripStay(trip: Trip, date: globalThis.Date, timezone?: string): Stay | undefined {
    const timestamp = fromZonedTime(date, getTimezoneOrDefault(timezone)).getTime() / 1000
    return trip.stays?.findLast(stay => stay.start <= timestamp && (timestamp + ONE_DAY_SECONDS) < stay.end)
}

export function getTripPublicHoliday(trip: Trip, day: globalThis.Date): PublicHoliday | undefined {
    const dateString = format(day, PUBLIC_HOLIDAY_DATE_FORMAT)
    return trip.publicHolidays?.find(h => h.date === dateString)
}

export function getTripDaysCount(trip: Trip, timezone?: string): number {
    return differenceInCalendarDays(startOfDay(getZonedDate(trip.end! - 1, getTimezoneOrDefault(timezone))), startOfDay(getZonedDate(trip.start!, getTimezoneOrDefault(timezone)))) + 1
}
