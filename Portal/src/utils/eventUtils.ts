import { fromUnixTime, startOfDay } from "date-fns"
import type { TimeTrackingEvent } from "../types/CoreSwaggerTypes"
import { toZonedTime } from "date-fns-tz"
import { getZonedDate } from "./timeUtils"

export function getEvents(date: Date, events: TimeTrackingEvent[] | null, filterHours: (hours: number) => boolean, timezone: string) {
    const targetStartOfDayTime = startOfDay(date).getTime()
    return (events ?? []).filter(event => filterHours(event.hours) && startOfDay(getZonedDate(event.timestamp, timezone)).getTime() === targetStartOfDayTime)
}

export function getEventHoursSum(events: TimeTrackingEvent[] | null): number {
    return (events ?? []).map(event => event.hours).reduce((sum, value) => sum + value, 0)
}