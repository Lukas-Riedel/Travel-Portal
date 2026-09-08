import { startOfDay } from "date-fns"
import { ClockPlus, type LucideIcon,Palmtree, Pill, Shield } from "lucide-react"

import { type TimeTrackingEvent,TimeTrackingEventType } from "../types/CoreSwaggerTypes"
import { getZonedDate } from "./timeUtils"

export const HOURS_PER_MAN_DAY = 8
export const TIME_TRACKING_EVENT_TYPE_ICONS: Partial<Record<TimeTrackingEventType, LucideIcon>> = {
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