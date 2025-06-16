import { useMemo } from "react"
import PhotoTile from "./PhotoTile"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"

export default function YearTile({ year }) {
    const yearPlaces = useTimeFilteredRegularPlaces({ year: year?.id, include: "CATEGORIES" })
    const categories = useMemo(() => [...new Map(yearPlaces?.map(place => place.getCategory("COUNTRY"))?.filter(category => category)
        ?.map(category => [category.name, category])).values()]?.sort((a, b) => a.name.localeCompare(b.name)), [yearPlaces])

    return (
        <PhotoTile
            src={year?.mainHighlight?.url?.thumbnail ?? year?.mainHighlight?.url?.full}
            firstLineText={year?.id}
            categories={categories}
            to={year ? "/year/" + year?.id : "#"} />
    )
}
