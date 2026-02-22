import { format, isWeekend } from "date-fns"
import { getCurrentYear, ONE_MONTH_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"
import { useConfiguration } from "../contexts/ConfigContext.jsx"
import type { UsePublicHolidaysResult } from "../types/UsePublicHolidaysResult.ts"
import { useCallback } from "react"

export const usePublicHolidays = (maxYear?: number): UsePublicHolidaysResult => {
    const { configuration } = useConfiguration()

    const { response: publicHolidays = [], isLoading } = useQuery({
        queryKey: ["publicHolidays", `${getCurrentYear()}`, `${maxYear}`],
        queryFn: () => fetchAllPublicHolidays(configuration?.homeLocation?.countryCode, maxYear),
        staleTime: ONE_MONTH_SECONDS * 1000,
        enabled: !!configuration?.homeLocation?.countryCode,
        refetchOnWindowFocus: false
    })

    const isPublicHoliday = useCallback((date: Date) => {
        if (isLoading) {
            return false
        }

        const localDateString = formatLocalDate(date)
        return publicHolidays.some(publicHoliday => publicHoliday.date === localDateString)
    }, [isLoading, publicHolidays])

    const isFreeDay = useCallback((date: Date) => {
        if (isLoading) {
            return false
        }

        return isWeekend(date) || isPublicHoliday(date)
    }, [isLoading, isPublicHoliday])

    return {
        isPublicHoliday,
        isFreeDay
    }
}

async function fetchAllPublicHolidays(countryCode: string, maxYear?: number): Promise<PublicHolidaysResponse> {
    const currentYear = getCurrentYear()
    const years = [...Array((maxYear ?? currentYear) - currentYear + 2)].map((_, i) => currentYear - 1 + i)
    return (await Promise.all(years.map(year => fetchPublicHolidays(year, countryCode)))).flat()
}

async function fetchPublicHolidays(year: number, countryCode: string): Promise<PublicHolidaysResponse> {
    return (await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/${countryCode}`)).json()
}

function formatLocalDate(date: Date): string {
    // This format matches the one used by the API.
    return format(date, "yyyy-MM-dd")
}

type PublicHolidaysResponse = { date: string }[]