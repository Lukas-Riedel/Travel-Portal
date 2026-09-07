import { useMemo } from "react"
import { useTranslation } from "react-i18next"

import type { Trip } from "../classes/Trip"
import { useCategories } from "../hooks/useCategories"
import { CategoryCategory } from "../types/CoreSwaggerTypes"
import { formatDateRange } from "../utils/timeUtils"
import PhotoTile from "./PhotoTile"

interface TripTileProps {
    trip: Trip
}

export default function TripTile({ trip }: TripTileProps) {
    const { t } = useTranslation()

    const countryCategories = useCategories({ categories: [CategoryCategory.Country] })
    const categories = useMemo(() => countryCategories?.filter(category => trip.countries?.some(country => country === category.name))?.sort((a, b) => a.name.localeCompare(b.name)), [trip, countryCategories])

    return (
        <PhotoTile
            src={trip.mainHighlight?.url?.thumbnail ?? trip.mainHighlight?.url?.full}
            firstLineText={trip.getFullName()}
            secondLineText={formatDateRange(trip.start, trip.end, t("general.format.date.year.included"))}
            categories={categories}
            to={trip} />
    )
}