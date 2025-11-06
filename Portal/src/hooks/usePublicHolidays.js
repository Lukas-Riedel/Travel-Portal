import { useQuery } from "@tanstack/react-query"
import { format } from "date-fns"

export const usePublicHolidays = maxYear => {
    const currentYear = new Date().getFullYear()

    // TODO: Extract the country to a constant.
    const fetchHolidays = async year => (await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/CZ`))?.json()
    const formatLocalDate = date => format(date, "yyyy-MM-dd")

    const { data: holidays = [], isLoading } = useQuery({
        queryKey: ["publicHolidays", currentYear, maxYear],
        queryFn: async () => (await Promise.all([...Array((maxYear ?? currentYear) - currentYear + 1)].map((_, i) => currentYear + i).map(fetchHolidays))).flat(),
        staleTime: 1000 * 60 * 60 * 24
    })

    const isPublicHoliday = date => {
        if (isLoading || !date) {
            return false
        }

        const localDateStr = formatLocalDate(date)
        return holidays.some(h => h.date === localDateStr)
    }

    return {
        isPublicHoliday,
        isFreeDay: date => date.getDay() === 0 || date.getDay() === 6 || isPublicHoliday(date)
    }
}
