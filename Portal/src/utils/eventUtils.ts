import { fromUnixTime, startOfDay } from "date-fns"
import { TimeTrackingEventType, type TimeTrackingEvent } from "../types/CoreSwaggerTypes"
import { toZonedTime } from "date-fns-tz"
import { getZonedDate } from "./timeUtils"
import { ClockPlus, Palmtree, Pill, Shield } from "lucide-react"

export const HOURS_PER_MAN_DAY = 8
export const TIME_TRACKING_EVENT_TYPE_ICONS = {
    [TimeTrackingEventType.Overtime]: ClockPlus,
    [TimeTrackingEventType.Vacation]: Palmtree,
    [TimeTrackingEventType.Selfcare]: Pill,
    [TimeTrackingEventType.Tenure]: Shield
}

export function getEvents(date: Date, events: TimeTrackingEvent[] | null, filterHours: (hours: number) => boolean, timezone: string) {
    const targetStartOfDayTime = startOfDay(date).getTime()
    return (events ?? []).filter(event => filterHours(event.hours) && startOfDay(getZonedDate(event.timestamp, timezone)).getTime() === targetStartOfDayTime)
}

export function getEventHoursSum(events: TimeTrackingEvent[] | null): number {
    return (events ?? []).map(event => event.hours).reduce((sum, value) => sum + value, 0)
}