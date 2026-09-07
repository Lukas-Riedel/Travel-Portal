import { differenceInCalendarDays, endOfDay, format, fromUnixTime, isSameDay, startOfDay } from "date-fns"
import type { Date, Expense, Fitness, Flight, Highlight, Trip as ITrip, Note, Place, PublicHoliday, Statistics, Stay, Task } from "../types/CoreSwaggerTypes.ts"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { getCurrentTimestamp, getEndOfTodayOrMaximumAllowedTimestamp, getCurrentOrMaximumAllowedTimestamp, getStartOfTodayOrMaximumAllowedTimestamp, getTimezoneOrDefault, getZonedDate, ONE_DAY_SECONDS, getMaximumAllowedTimetamp } from "../utils/timeUtils.ts"
import { getTripFullName } from "../utils/formattingUtils.ts"
import { getCalendarEvents, isTripCandidate } from "../utils/tripUtils.ts"

const PUBLIC_HOLIDAY_DATE_FORMAT = "d.M.yyyy"

export class Trip implements ITrip {
    id: string
    name: string
    year?: number
    mainHighlight?: Highlight
    start?: number
    end?: number
    countries?: string[]
    expenses?: Expense[]
    stays?: Stay[]
    flights?: Flight[]
    watchedFlights?: Flight[]
    fitness?: Fitness[]
    notes?: Note[]
    highlights?: Highlight[]
    statistics?: Statistics[]
    publicHolidays?: PublicHoliday[]
    tasks?: Task[]

    public constructor(data: ITrip) {
        Object.assign(this, data)
    }

    public getFullName(): string {
        return getTripFullName(this)
    }

    public isCandidate(): boolean {
        return isTripCandidate(this)
    }

    public getCalendarEvents(date: globalThis.Date, places: Place[], timezone?: string): (Flight | (Place & Date))[] {
        return getCalendarEvents(date, this.flights, this.watchedFlights, places, timezone)
    }

    public isPast(): boolean {
        return this.end < getCurrentOrMaximumAllowedTimestamp()
    }

    public isFuture(): boolean {
        return this.start > getCurrentOrMaximumAllowedTimestamp()
    }

    public isCurrent(): boolean {
        return this.start <= getEndOfTodayOrMaximumAllowedTimestamp() && getStartOfTodayOrMaximumAllowedTimestamp() < this.end
    }

    public isDayInTrip(date: globalThis.Date): boolean {
        return this.start * 1000 <= endOfDay(date).getTime() && startOfDay(date).getTime() < this.end * 1000
    }

    public isBetweenDates(start: globalThis.Date, end: globalThis.Date, timezone?: string): boolean {
        return start < getZonedDate(this.end, timezone) && getZonedDate(this.start, timezone) < end
    }

    public isStartDayOfTrip(date: globalThis.Date): boolean {
        return isSameDay(fromUnixTime(this.start), date)
    }

    public isEndDayOfTrip(date: globalThis.Date): boolean {
        return isSameDay(fromUnixTime(this.end), date)
    }

    public getStay(date: globalThis.Date, timezone?: string): Stay | undefined {
        const timestamp = fromZonedTime(date, getTimezoneOrDefault(timezone)).getTime() / 1000
        return this.stays?.findLast(stay => stay.start <= timestamp && (timestamp + ONE_DAY_SECONDS) < stay.end)
    }

    public getPublicHoliday(day: globalThis.Date): PublicHoliday | undefined {
        const dateString = format(day, PUBLIC_HOLIDAY_DATE_FORMAT)
        return this.publicHolidays?.find(h => h.date === dateString)
    }

    public getDaysCount(timezone?: string): number {
        return differenceInCalendarDays(startOfDay(getZonedDate(this.end - 1, timezone)), startOfDay(getZonedDate(this.start, timezone))) + 1
    }
}