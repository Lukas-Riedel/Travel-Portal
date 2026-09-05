import { addDays, eachDayOfInterval, endOfDay, format, fromUnixTime, isSameDay, startOfDay } from "date-fns"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { getCoordinates } from "../clients/coreClient.ts"
import type { Trip } from "../classes/Trip.ts"
import type { Place } from "../classes/Place.ts"

export const ONE_MINUTE_SECONDS = 60
export const ONE_HOUR_SECONDS = 60 * ONE_MINUTE_SECONDS
export const ONE_DAY_SECONDS = 24 * ONE_HOUR_SECONDS
export const ONE_WEEK_SECONDS = 7 * ONE_DAY_SECONDS
export const ONE_MONTH_SECONDS = 30 * ONE_DAY_SECONDS

export function getCurrentYear(): number {
    return new Date().getFullYear()
}

export function getCurrentTimestamp(): number {
    return Math.floor(Date.now() / 1000)
}

export function getMaximumAllowedTimetamp(): number {
    return window.env?.VITE_MAXIMUM_ALLOWED_TIMESTAMP || import.meta.env.VITE_MAXIMUM_ALLOWED_TIMESTAMP
}

export function getCurrentOrMaximumAllowedTimestamp(): number {
    return Math.min(getCurrentTimestamp(), getMaximumAllowedTimetamp())
}

export function getStartOfTodayOrMaximumAllowedTimestamp(): number {
    return Math.min(startOfDay(new Date()).getTime() / 1000, getMaximumAllowedTimetamp())
}

export function getEndOfTodayOrMaximumAllowedTimestamp(): number {
    return Math.min(endOfDay(new Date()).getTime() / 1000, getMaximumAllowedTimetamp())
}

export function getZonedDate(dateOrTimestamp: number | Date, timezone: string): Date {
    return typeof dateOrTimestamp === "number"
        ? toZonedTime(fromUnixTime(dateOrTimestamp), timezone)
        : toZonedTime(dateOrTimestamp, timezone)
}

export function getTimezoneOrDefault(timezone?: string): string {
    return timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC"
}

export function formatTimestamp(dateOrTimestamp: number | Date, timestampFormat: string, timezone?: string) {
    return format(toZonedTime(fromUnixTime(typeof dateOrTimestamp === "number" ? dateOrTimestamp : (dateOrTimestamp.getTime() / 1000)), getTimezoneOrDefault(timezone)), timestampFormat)
}

export function formatDateRange(startDateOrTimestamp: number | Date, endDateOrTimestamp: number | Date, timestampFormat: string): string {
    const startFormatted = formatTimestamp(startDateOrTimestamp, timestampFormat)
    const endFormatted = formatTimestamp(endDateOrTimestamp, timestampFormat)

    if (startFormatted === endFormatted) {
        return startFormatted
    }

    const delimiter = startFormatted.match(/[^a-zA-Z0-9]/)?.[0]
    if (!delimiter) {
        return `${startFormatted} - ${endFormatted}`
    }

    const startParts = startFormatted.split(delimiter)
    const endParts = endFormatted.split(delimiter)

    while (startParts.length > 1 && startParts[startParts.length - 1] === endParts[startParts.length - 1]) {
        startParts.pop()
    }

    const shortStart = startParts.join(delimiter) + delimiter
    return `${shortStart} - ${endFormatted}`
}

export function getDate(dateOrTimestamp: number | Date): Date {
    return typeof dateOrTimestamp === "number" ? new Date(dateOrTimestamp * 1000) : dateOrTimestamp
}

export function getDayIndex(dateOrTimestamp: number | Date): number {
    return (typeof dateOrTimestamp === "number" ? Math.floor(dateOrTimestamp / ONE_DAY_SECONDS) : Math.floor(dateOrTimestamp.getTime() / (1000 * ONE_DAY_SECONDS))) + 2
}

export async function getAirportTimezone(airportName: string): Promise<string> {
    return (await getCoordinates(airportName))?.timezone
}

export async function getAirportLocalTime(airportName: string, datetime: Date): Promise<number> {
    return Math.round(fromZonedTime(datetime, await getAirportTimezone(airportName))?.getTime() / 1000)
}

export function isToday(timestamp: number): boolean {
    return isSameDay(fromUnixTime(timestamp), new Date())
}

export function getDaysFromTodayThrough(end: number, offsetDays?: number): Date[] {
    return eachDayOfInterval({
        start: startOfDay(offsetDays ? addDays(new Date(), 1) : new Date()),
        end: startOfDay(fromUnixTime(end))
    })
}

// TODO: Move to Trip?
export function getTripDays(trip?: Trip, places?: Place[], timezone?: string): Date[] | undefined {
    const realTripStart = trip?.start ?? (places && places.length > 0 ? Math.min(...places.flatMap(place => place?.dates ?? []).map(date => date.start)) : undefined)
    const realTripEnd = trip?.end ?? (places && places.length > 0 ? Math.max(...places.flatMap(place => place?.dates ?? []).map(date => date.end)) : undefined)

    return realTripStart && realTripEnd && eachDayOfInterval({
        start: startOfDay(toZonedTime(fromUnixTime(realTripStart), timezone)),
        end: startOfDay(toZonedTime(fromUnixTime(realTripEnd - 1), timezone))
    })
}

export function isTodayOrFutureDay(date: Date, timezone?: string): boolean {
    const todayStart = startOfDay(toZonedTime(new Date(), timezone))
    return date >= todayStart
}

export function getCurrentHour(timezone?: string): number {
    return toZonedTime(new Date(), timezone || "UTC").getHours()
}

export function isBeginningOfCurrentYear(date: Date): boolean {
    return date.getDate() === 1 && date.getMonth() === 0 && date.getFullYear() !== new Date().getFullYear()
}