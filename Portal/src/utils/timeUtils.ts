import { endOfDay, format, fromUnixTime, startOfDay } from "date-fns"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { getCoordinates } from "../clients/coreClient.ts"

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