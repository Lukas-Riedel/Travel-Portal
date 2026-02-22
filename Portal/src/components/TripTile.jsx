import { useMemo } from "react"
import { getDateRangeString } from "../utils/helpers"
import PhotoTile from "./PhotoTile"
import { useCategories } from "../hooks/useCategories"

export default function TripTile({ trip }) {
    const countryCategories = useCategories({ categories: ["country"] })

    const categories = useMemo(() => countryCategories?.filter(category => trip?.countries?.some(country => country === category.name))?.sort((a, b) => a.name.localeCompare(b.name)), [trip, countryCategories])

    return (
        <PhotoTile
            src={trip?.mainHighlight?.url?.thumbnail ?? trip?.mainHighlight?.url?.full}
            firstLineText={trip?.getFullName()}
            secondLineText={getDateRangeString(trip?.start, trip?.end)}
            categories={categories}
            to={"/trip/" + trip?.id} />
    )
}