import { endOfDay, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"

export const ONE_DAY_SECONDS = 86400
export const ONE_HOUR_SECONDS = 3600

export function getCurrentTimestamp(): number {
    return Math.floor(Date.now() / 1000)
}

export function getStartOfTodayTimestamp(): number {
    return startOfDay(new Date()).getTime() / 1000
}

export function getEndOfTodayTimestamp(): number {
    return endOfDay(new Date()).getTime() / 1000
}

export function getZonedDate(dateOrTimestamp: number | Date, timezone: string): Date {
    return typeof dateOrTimestamp === "number"
        ? toZonedTime(fromUnixTime(dateOrTimestamp), timezone)
        : toZonedTime(dateOrTimestamp, timezone)
}

export function getTimezoneOrDefault(timezone?: string): string {
    return timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC"
}