import { endOfDay, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"

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