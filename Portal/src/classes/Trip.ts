import { differenceInCalendarDays, endOfDay, format, fromUnixTime, isSameDay, startOfDay } from "date-fns"
import type { Date, Expense, Fitness, Flight, Highlight, Trip as ITrip, Note, Place, PublicHoliday, Statistics, Stay } from "../types/CoreSwaggerTypes.ts"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { getCurrentTimestamp, getEndOfTodayOrMaximumAllowedTimestamp, getCurrentOrMaximumAllowedTimestamp, getStartOfTodayOrMaximumAllowedTimestamp, getTimezoneOrDefault, getZonedDate, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { getTripFullName, isTripCandidate } from "../utils/formattingUtils.ts"

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

    public constructor(data: ITrip) {
        Object.assign(this, data)
    }

    public isCandidate(): boolean {
        return isTripCandidate(this)
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
        return start < toZonedTime(fromUnixTime(this.end), timezone) && toZonedTime(fromUnixTime(this.start), timezone) < end
    }

    public isStartDayOfTrip(date: globalThis.Date): boolean {
        return isSameDay(fromUnixTime(this.start), date)
    }

    public isEndDayOfTrip(date: globalThis.Date): boolean {
        return isSameDay(fromUnixTime(this.end), date)
    }

    public getFullName(): string {
        return getTripFullName(this)
    }

    public getCalendarEvents(date: globalThis.Date, places: Place[], timezone?: string): (Flight | (Place & Date))[] {
        const flightEvents = (this.flights ?? [])
            .filter(f => isSameDay(date, getZonedDate(f.start, timezone || f.from.timezone)))
        const watchedFlightEvents = (this.watchedFlights ?? [])
            .filter(f => isSameDay(date, getZonedDate(f.start, timezone || f.from.timezone)))
            .map(f => ({ ...f, flight: undefined }))
        const placeEvents = (places ?? []).flatMap(place => place.dates
            .filter(d => isSameDay(date, getZonedDate(d.start, timezone || place.timezone)))
            .map(date => ({ ...date, ...place })))
        return [...flightEvents, ...watchedFlightEvents, ...placeEvents].sort((a, b) => a.start - b.start)
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