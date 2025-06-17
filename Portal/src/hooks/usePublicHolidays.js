import { useQuery } from "@tanstack/react-query"
import { useConfiguration } from "../contexts/ConfigContext"
import { format } from "date-fns"

export const usePublicHolidays = () => {
    const configuration = useConfiguration()

    const currentYear = new Date().getFullYear()

    const fetchHolidays = async year => (await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/CZ`))?.json()
    const formatLocalDate = date => format(date, "yyyy-MM-dd")

    const { data: holidays = [], isLoading } = useQuery({
        queryKey: ["publicHolidays", currentYear],
        queryFn: () => fetchHolidays(currentYear),
        staleTime: 1000 * 60 * 60 * 24
    })

    const isPublicHoliday = date => {
        if (isLoading || !date) {
            return false
        }

        const year = date.getFullYear()
        if (year !== currentYear) {
            return false
        }

        const localDateStr = formatLocalDate(date)
        return holidays.some(h => h.date === localDateStr)
    }

    return {
        isPublicHoliday
    }
}
