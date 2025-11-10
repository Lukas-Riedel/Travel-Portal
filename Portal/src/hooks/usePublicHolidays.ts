import { format, isWeekend } from "date-fns"
import { getCurrentYear, ONE_MONTH_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"
import { useConfiguration } from "../contexts/ConfigContext.jsx"
import type { UsePublicHolidaysResult } from "../types/UsePublicHolidaysResult.ts"

export const usePublicHolidays = (maxYear?: number): UsePublicHolidaysResult => {
    const { configuration } = useConfiguration()

    const { response: publicHolidays = [], isLoading } = useQuery({
        queryKey: ["publicHolidays", `${getCurrentYear()}`, `${maxYear}`],
        queryFn: () => fetchAllPublicHolidays(configuration?.homeLocation?.countryCode, maxYear),
        staleTime: ONE_MONTH_SECONDS * 1000,
        enabled: !!configuration?.homeLocation?.countryCode,
        refetchOnWindowFocus: false
    })

    return {
        isPublicHoliday: (date: Date) => !isLoading && isPublicHoliday(publicHolidays, date),
        isFreeDay: (date: Date) => !isLoading && (isWeekend(date) || isPublicHoliday(publicHolidays, date))
    }
}

async function fetchAllPublicHolidays(countryCode: string, maxYear?: number): Promise<PublicHolidaysResponse> {
    const currentYear = getCurrentYear()
    const years = [...Array((maxYear ?? currentYear) - currentYear + 1)].map((_, i) => currentYear + i)
    return (await Promise.all(years.map(year => fetchPublicHolidays(year, countryCode)))).flat()
}

async function fetchPublicHolidays(year: number, countryCode: string): Promise<PublicHolidaysResponse> {
    return (await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/${countryCode}`)).json()
}

function formatLocalDate(date: Date): string {
    // This format matches the one used by the API.
    return format(date, "yyyy-MM-dd")
}

function isPublicHoliday(publicHolidays: PublicHolidaysResponse, date: Date): boolean {
    const localDateString = formatLocalDate(date)
    return publicHolidays.some(publicHoliday => publicHoliday.date === localDateString)
}

type PublicHolidaysResponse = { date: string }[]